<?php

declare(strict_types=1);

use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('customer_name', 200);
            $t->string('customer_email', 200);
            $t->decimal('total', 12, 2);
            $t->string('status', 50)->default('pending');
            $t->text('items');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('orders');
    }
};
