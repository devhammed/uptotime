<?php

declare(strict_types=1);

arch()
    ->expect('App')
    ->toUseStrictTypes()
    ->toUseStrictEquality()
    ->toBeCasedCorrectly();

arch()->preset()->php();

arch()->preset()->security();

arch()->preset()->laravel();
