<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLureRequest;
use App\Http\Requests\UpdateLureRequest;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Favorite;
use App\Models\Lure;
use App\Models\LureImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LureController extends Controller
{
    // 自分の出品一覧
    public function index(Request $request): View
    {
        $lures = Lure::forSeller($request->user()->id)
            ->with(['images' => fn($q) => $q->where('display_order', 0)])
            ->latest()
            ->paginate(20);

        return view('lures.index', compact('lures'));
    }

    // 出品フォーム（新規）
    public function create(): View
    {
        $brands = Brand::active()->orderBy('name_kana')->get();
        $colors = Color::active()->orderBy('name_kana')->get();
        $prefectures = $this->prefectures();

        return view('lures.create', compact('brands', 'colors', 'prefectures'));
    }

    // 画像一時アップロード（選択時にAJAXで呼ばれる）
    public function storeTempImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $path = $request->file('image')->store('temp/lures', 'public');

        return response()->json([
            'path' => $path,
            'url'  => Storage::disk('public')->url($path),
        ]);
    }

    // 動画一時アップロード
    public function storeTempVideo(Request $request): JsonResponse
    {
        $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:102400'],
        ]);

        $path = $request->file('video')->store('temp/lures/videos', 'public');

        return response()->json([
            'path' => $path,
            'url'  => Storage::disk('public')->url($path),
        ]);
    }

    // 出品保存（新規）
    public function store(StoreLureRequest $request): RedirectResponse
    {
        $isDraft = $request->input('form_action') === 'draft';
        $status  = $isDraft ? 'draft' : 'active';

        $lure = DB::transaction(function () use ($request, $status) {
            $lure = Lure::create(array_merge(
                $this->buildLureData($request),
                ['seller_id' => $request->user()->id, 'status' => $status]
            ));

            $this->moveTempImages($request, $lure);
            $this->moveTempVideo($request, $lure);

            return $lure;
        });

        if ($isDraft) {
            return redirect()->route('lures.edit', $lure)
                ->with('success', '下書きとして保存しました。');
        }

        return redirect()->route('lures.show', $lure)
            ->with('success', 'ルアーを出品しました。');
    }

    // 商品詳細
    public function show(Lure $lure): View
    {
        // 削除済みは404
        abort_if($lure->status === 'deleted', 404);

        $lure->load(['seller', 'brand', 'color', 'images']);
        $lure->loadCount('favorites');

        $isFavorited = auth()->check()
            ? Favorite::where('user_id', auth()->id())->where('lure_id', $lure->id)->exists()
            : false;

        // 同カテゴリの関連商品（自分以外・出品中・最大4件）
        $similarLures = Lure::active()
            ->where('category', $lure->category)
            ->where('id', '!=', $lure->id)
            ->with(['images' => fn ($q) => $q->where('display_order', 0)])
            ->withCount('favorites')
            ->latest()
            ->limit(4)
            ->get();

        return view('lures.show', compact('lure', 'isFavorited', 'similarLures'));
    }

    // 編集フォーム
    public function edit(Lure $lure): View
    {
        $this->authorize('update', $lure);

        $brands = Brand::active()->orderBy('name_kana')->get();
        $colors = Color::active()->orderBy('name_kana')->get();
        $prefectures = $this->prefectures();
        $lure->load('images');

        return view('lures.edit', compact('lure', 'brands', 'colors', 'prefectures'));
    }

    // 編集保存
    public function update(UpdateLureRequest $request, Lure $lure): RedirectResponse
    {
        $this->authorize('update', $lure);

        $isDraft = $request->input('form_action') === 'draft';
        $status  = $isDraft ? 'draft' : ($lure->status === 'draft' ? 'active' : $lure->status);

        DB::transaction(function () use ($request, $lure, $status) {
            $lure->update(array_merge(
                $this->buildLureData($request),
                ['status' => $status]
            ));

            // 1. 削除対象の画像をストレージごと削除
            $deleteIds = $request->input('delete_image_ids', []);
            if (!empty($deleteIds)) {
                $images = LureImage::where('lure_id', $lure->id)
                    ->whereIn('id', $deleteIds)
                    ->get();
                foreach ($images as $image) {
                    Storage::disk('public')->delete($image->image_path);
                    $image->delete();
                }
            }

            // 2. ユーザーが指定した並び順を保存（image_order[] は削除されていない画像のIDが順番に並ぶ）
            foreach ($request->input('image_order', []) as $order => $imageId) {
                // セキュリティ: 自分のルアーの画像のみ更新
                LureImage::where('id', $imageId)
                    ->where('lure_id', $lure->id)
                    ->update(['display_order' => (int) $order]);
            }

            // 3. 新規画像を末尾に追加（削除・並び替え後の枚数を起点にする）
            $this->storeImages($request, $lure);
            $this->moveTempVideo($request, $lure);
        });

        $message = $isDraft ? '下書きを更新しました。' : '商品情報を更新しました。';
        return redirect()->route('lures.show', $lure)->with('success', $message);
    }

    // 削除
    public function destroy(Lure $lure): RedirectResponse
    {
        $this->authorize('delete', $lure);

        // 画像ファイルを削除してからステータスを削除済みに変更
        foreach ($lure->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        $lure->images()->delete();
        $lure->update(['status' => 'deleted']);

        return redirect()->route('lures.index')
            ->with('success', '商品を削除しました。');
    }

    // 一時停止
    public function suspend(Lure $lure): RedirectResponse
    {
        $this->authorize('suspend', $lure);
        $lure->update(['status' => 'suspended']);

        return back()->with('success', '出品を一時停止しました。');
    }

    // 再開
    public function resume(Lure $lure): RedirectResponse
    {
        $this->authorize('resume', $lure);
        $lure->update(['status' => 'active']);

        return back()->with('success', '出品を再開しました。');
    }

    // 値下げ
    public function markDown(Request $request, Lure $lure): RedirectResponse
    {
        $this->authorize('update', $lure);

        $request->validate([
            'price' => ['required', 'integer', 'min:1', 'max:9999999', 'lt:' . $lure->price],
        ], [
            'price.lt' => '値下げ後の価格は現在の価格より低くしてください。',
        ]);

        $lure->update(['price' => $request->price]);

        return back()->with('success', '価格を値下げしました。');
    }

    // ---- private ----

    private function buildLureData(StoreLureRequest|UpdateLureRequest $request): array
    {
        return $request->safe()->except(['images', 'form_action', 'delete_image_ids', 'temp_video_path', 'temp_image_paths']) + [
            'is_handmade'     => $request->boolean('is_handmade'),
            'is_discontinued' => $request->boolean('is_discontinued'),
            'is_limited'      => $request->boolean('is_limited'),
        ];
    }

    private function storeImages(StoreLureRequest|UpdateLureRequest $request, Lure $lure): void
    {
        if (!$request->hasFile('images')) {
            return;
        }

        $currentCount = $lure->images()->count();

        foreach ($request->file('images') as $index => $file) {
            if ($currentCount + $index >= 10) {
                break; // 最大10枚
            }

            // セキュリティ: ランダムファイル名で保存（元のファイル名は使わない）
            $path = $file->store('lures', 'public');

            LureImage::create([
                'lure_id'       => $lure->id,
                'image_path'    => $path,
                'display_order' => $currentCount + $index,
            ]);
        }
    }

    private function moveTempVideo(StoreLureRequest|UpdateLureRequest $request, Lure $lure): void
    {
        $tempPath = $request->input('temp_video_path');
        if (!$tempPath) {
            return;
        }

        // パストラバーサル防止
        if (!preg_match('/^temp\/lures\/videos\/[a-zA-Z0-9_\-]+\.(mp4|mov|webm)$/i', $tempPath)) {
            return;
        }

        if (!Storage::disk('public')->exists($tempPath)) {
            return;
        }

        // 既存動画があれば削除
        if ($lure->video_path && Storage::disk('public')->exists($lure->video_path)) {
            Storage::disk('public')->delete($lure->video_path);
        }

        $newPath = 'lures/videos/' . basename($tempPath);
        Storage::disk('public')->move($tempPath, $newPath);
        $lure->update(['video_path' => $newPath]);
    }

    // 一時アップロード済み画像を本番ディレクトリへ移動（新規出品時）
    private function moveTempImages(StoreLureRequest $request, Lure $lure): void
    {
        $tempPaths = array_filter((array) $request->input('temp_image_paths', []));

        foreach (array_values(array_slice($tempPaths, 0, 10)) as $index => $tempPath) {
            $newPath = 'lures/' . basename($tempPath);
            Storage::disk('public')->move($tempPath, $newPath);

            LureImage::create([
                'lure_id'       => $lure->id,
                'image_path'    => $newPath,
                'display_order' => $index,
            ]);
        }
    }

    private function prefectures(): array
    {
        return [
            '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
            '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
            '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県',
        ];
    }
}
