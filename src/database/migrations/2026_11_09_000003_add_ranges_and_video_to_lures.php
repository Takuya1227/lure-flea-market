<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('lures', function (Blueprint $table) {
            // 旧 range（enum）の後に追加（複数選択対応の新カラム）
            $table->json('ranges')->nullable()->after('range');
            $table->string('video_path')->nullable()->after('ranges');
        });
    }
    public function down(): void {
        Schema::table('lures', function (Blueprint $table) {
            $table->dropColumn(['ranges', 'video_path']);
        });
    }
};
