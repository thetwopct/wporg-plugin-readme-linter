<?php

declare(strict_types=1);

namespace WPOrg\Plugin\ReadmeLinter\Parser;

use WordPressdotorg\Plugin_Directory\Readme\Parser;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

// Load WordPress function polyfills
require_once __DIR__ . '/wp-polyfills.php';

// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols

class ReadmeParser
{
    /**
     * Parse a readme file.
     *
     * @return array<string, mixed>
     */
    public function parse(string $content): array
    {
        $parser = new Parser($content);

        return [
            'name' => $parser->name ?: '',
            'contributors' => $parser->contributors ?: [],
            'tags' => $parser->tags ?: [],
            'requires' => $parser->requires ?: '',
            'tested' => $parser->tested ?: '',
            'requires_php' => $parser->requires_php ?: '',
            'stable_tag' => $parser->stable_tag ?: '',
            'donate_link' => $parser->donate_link ?: '',
            'short_description' => $parser->short_description ?: '',
            'sections' => $parser->sections ?: [],
            'upgrade_notice' => $parser->upgrade_notice ?: [],
            'screenshots' => $parser->screenshots ?: [],
        ];
    }
}
