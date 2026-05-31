<?php

declare(strict_types=1);

use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class {
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $t) {
            $t->smallint('is_active')->default(1);
            $t->string('color', 20)->nullable();
            $t->string('description')->nullable();
            $t->integer('sort_order')->default(0);
            $t->integer('parent_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $t) {
            $t->dropColumn('is_active');
            $t->dropColumn('color');
            $t->dropColumn('description');
            $t->dropColumn('sort_order');
            $t->dropColumn('parent_id');
        });
    }
};
