<?php

declare(strict_types=1);

use Ergebnis\License;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$license = License\Type\None::text(
    License\Range::since(
        License\Year::fromString('2025'),
        new DateTimeZone('UTC')
    ),
    License\Holder::fromString('Marcos "Marcão" Aurelio'),
    License\Url::fromString('https://github.com/maco-studios/console')
);

$finder = Finder::create()
    ->in(
        __DIR__ . "/src"
    );

return (new Config())
    ->setFinder($finder)
    ->setRules([
        'header_comment' => [
            'comment_type' => 'PHPDoc',
            'header' => trim($license->header()),
            'location' => 'after_declare_strict',
            'separate' => 'both',
        ],
    ]);
