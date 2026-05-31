<?php

declare(strict_types=1);

use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->string('cover_image', 500)->nullable();
            $t->string('short_description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->dropColumn('cover_image');
            $t->dropColumn('short_description');
        });
    }
};
