<?php

declare(strict_types=1);

use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class {
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $t) {
            $t->id();
            $t->string('job');
            $t->text('data');
            $t->integer('attempts')->default(0);
            $t->integer('max_attempts')->default(3);
            $t->integer('priority')->default(0);
            $t->integer('timeout')->default(120);
            $t->bigint('available_at');
            $t->bigint('locked_until')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index('available_at');
            $t->index('locked_until');
        });

        Schema::create('failed_jobs', function (Blueprint $t) {
            $t->id();
            $t->string('job');
            $t->text('data');
            $t->text('error');
            $t->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::drop('failed_jobs');
        Schema::drop('jobs');
    }
};
