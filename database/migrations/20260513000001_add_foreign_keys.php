<?php

declare(strict_types=1);

use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class {
    public function up(): void
    {
        Schema::table('refresh_tokens', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('orders', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('posts', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('products', function (Blueprint $t) {
            $t->integer('user_id');
            $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('refresh_tokens', function (Blueprint $t) {
            $t->dropForeign('refresh_tokens_user_id_foreign');
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->dropForeign('orders_user_id_foreign');
            $t->dropColumn('user_id');
        });
        Schema::table('posts', function (Blueprint $t) {
            $t->dropForeign('posts_user_id_foreign');
            $t->dropColumn('user_id');
        });
        Schema::table('products', function (Blueprint $t) {
            $t->dropForeign('products_user_id_foreign');
            $t->dropColumn('user_id');
        });
    }
};
