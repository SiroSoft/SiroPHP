<?php

declare(strict_types=1);

use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->decimal('price', 10, 2)->default(0);
            $t->integer('stock')->default(0);
            $t->string('category', 100)->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('products');
    }
};
