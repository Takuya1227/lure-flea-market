<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->recreateSqliteTable([
                'pending_payment', 'paid', 'shipped', 'review_pending',
                'completed', 'cancelled', 'disputed',
            ]);
            return;
        }

        // MySQL: preparing_shipment・delivered を削除し review_pending を追加
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM(
            'pending_payment',
            'paid',
            'shipped',
            'review_pending',
            'completed',
            'cancelled',
            'disputed'
        ) NOT NULL DEFAULT 'pending_payment'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->recreateSqliteTable([
                'pending_payment', 'paid', 'preparing_shipment', 'shipped',
                'delivered', 'completed', 'cancelled', 'disputed',
            ]);
            return;
        }

        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM(
            'pending_payment',
            'paid',
            'preparing_shipment',
            'shipped',
            'delivered',
            'completed',
            'cancelled',
            'disputed'
        ) NOT NULL DEFAULT 'pending_payment'");
    }

    private function recreateSqliteTable(array $statusValues): void
    {
        $statusIn = "'" . implode("','", $statusValues) . "'";

        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement("CREATE TABLE transactions_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lure_id INTEGER NOT NULL REFERENCES lures(id),
            buyer_id INTEGER NOT NULL REFERENCES users(id),
            seller_id INTEGER NOT NULL REFERENCES users(id),
            shipping_address_id INTEGER NOT NULL REFERENCES shipping_addresses(id),
            price INTEGER NOT NULL,
            fee_rate NUMERIC(4,3) NOT NULL DEFAULT 0.100,
            fee_amount INTEGER NOT NULL,
            seller_earnings INTEGER NOT NULL,
            status VARCHAR(255) NOT NULL DEFAULT 'pending_payment'
                CHECK (status IN ({$statusIn})),
            cancelled_by VARCHAR(255) NULL
                CHECK (cancelled_by IS NULL OR cancelled_by IN ('buyer','seller','admin')),
            cancel_reason TEXT NULL,
            auto_complete_at DATETIME NULL,
            paid_at DATETIME NULL,
            shipped_at DATETIME NULL,
            delivered_at DATETIME NULL,
            completed_at DATETIME NULL,
            cancelled_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )");

        DB::statement('INSERT INTO transactions_new SELECT * FROM transactions');
        DB::statement('DROP TABLE transactions');
        DB::statement('ALTER TABLE transactions_new RENAME TO transactions');

        DB::statement('CREATE INDEX transactions_buyer_id_index ON transactions (buyer_id)');
        DB::statement('CREATE INDEX transactions_seller_id_index ON transactions (seller_id)');
        DB::statement('CREATE INDEX transactions_lure_id_index ON transactions (lure_id)');
        DB::statement('CREATE INDEX transactions_status_index ON transactions (status)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
