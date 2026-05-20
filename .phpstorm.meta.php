<?php

namespace PHPSTORM_META {

    override(
        \Siro\Core\Container::make(0),
        map([
            '' => '@',
        ])
    );

    override(
        \Siro\Core\Container::getInstance(0),
        map([
            '' => '@',
        ])
    );

    override(
        \Siro\Core\App::resolve(0),
        map([
            '' => '@',
        ])
    );

    override(
        \Siro\Core\Database::connection(0),
        map([
            '' => \PDO::class,
        ])
    );

    override(
        \Siro\Core\Cache::get(0),
        map([
            '' => '@',
        ])
    );

    override(
        \Siro\Core\Event::dispatch(0),
        map([
            '' => '@',
        ])
    );
}
