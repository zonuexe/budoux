<?php

/*
 * Copyright 2023 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace Budoux;

use function array_is_list;
use function array_key_last;
use function array_map;
use function array_sum;
use function array_values;
use function file_get_contents;
use function json_decode;
use function mb_strlen;
use function strlen;

/**
 * The BudouX parser that translates the input sentence into phrases.
 *
 * You can create a parser instance by invoking `new Parser(model)` with the model data you
 * want to use. You can also create a parser by specifying the model file path with
 * `Parser.loadByFileName(modelFileName)`.
 *
 * In most cases, it's sufficient to use the default parser for the language. For example, you
 * can create a default Japanese parser as follows.
 *
 *     $parser = Parser::loadDefaultJapaneseParser();
 *
 */
class Parser
{
    /**
     * Constructs a BudouX parser.
     */
    public function __construct(
        /** @var array<string, array<string, int>> */
        private array $model,
    ) {
    }

    /**
     * Loads the default Japanese parser.
     */
    public static function loadDefaultJapaneseParser(): Parser
    {
        return self::loadByFileName(__DIR__ . '/../budoux/models/ja.json');
    }

    /**
     * Loads the default Simplified Chinese parser. Parser
     */
    public static function loadDefaultSimplifiedChineseParser(): Parser
    {
        return self::loadByFileName(__DIR__ . '/../budoux/models/zh-hans.json');
    }

    /**
     * Loads the default Traditional Chinese parser.
     */
    public static function loadDefaultTraditionalChineseParser(): Parser
    {
        return self::loadByFileName(__DIR__ . '/../budoux/models/zh-hant.json');
    }

    /**
     * Loads a parser by specifying the model file path.
     *
     * @param string $modelFileName the model file path.
     * @phpstan-param non-empty-string $modelFileName
     * @return Parser a BudouX parser.
     */
    public static function loadByFileName(string $modelFileName): Parser
    {
        $content = file_get_contents($modelFileName);
        assert($content !== false);

        /** @var array<string, array<string, int>> $model */
        $model = json_decode($content, true);

        return new self($model);
    }

    /**
     * Gets the score for the specified feature of the given sequence.
     *
     * @param string $featureKey the feature key to examine.
     * @param string $sequence the sequence to look up the score.
     * @return int the contribution score to support a phrase break.
     */
    private function getScore(string $featureKey, string $sequence): int
    {
        return $this->model[$featureKey][$sequence] ?? 0;
    }

    /**
     * Parses a sentence into phrases.
     *
     * @param string $sentence the sentence to break by phrase.
     * @return string[] a list of phrases.
     * @phpstan-return list<string>
     */
    public function parse(string $sentence): array
    {
        if (strlen($sentence) === 0) {
            return [];
        }

        $result = [
            mb_substr($sentence, 0, 1, 'UTF-8'),
        ];

        $totalScore = array_sum(array_map(array_sum(...), array_values($this->model)));
        $length = mb_strlen($sentence, 'UTF-8');

        for ($i = 1; $i < $length; $i++) {
            $score = -$totalScore;
            if ($i - 2 > 0) {
                $score += 2 * $this->getScore("UW1", mb_substr($sentence, $i - 3, 1, 'UTF-8'));
            }
            if ($i - 1 > 0) {
                $score += 2 * $this->getScore("UW2", mb_substr($sentence, $i - 2, 1, 'UTF-8'));
            }
            $score += 2 * $this->getScore("UW3", mb_substr($sentence, $i - 1, 1, 'UTF-8'));
            $score += 2 * $this->getScore("UW4", mb_substr($sentence, $i, 1, 'UTF-8'));
            if ($i + 1 < $length) {
                $score += 2 * $this->getScore("UW5", mb_substr($sentence, $i + 1, 1, 'UTF-8'));
            }
            if ($i + 2 < $length) {
                $score += 2 * $this->getScore("UW6", mb_substr($sentence, $i + 2, 1, 'UTF-8'));
            }
            if ($i > 1) {
                $score += 2 * $this->getScore("BW1", mb_substr($sentence, $i - 2, 2, 'UTF-8'));
            }
            $score += 2 * $this->getScore("BW2", mb_substr($sentence, $i - 1, 2, 'UTF-8'));
            if ($i + 1 < $length) {
                $score += 2 * $this->getScore("BW3", mb_substr($sentence, $i, 2, 'UTF-8'));
            }
            if ($i - 2 > 0) {
                $score += 2 * $this->getScore("TW1", mb_substr($sentence, $i - 3, 3, 'UTF-8'));
            }
            if ($i - 1 > 0) {
                $score += 2 * $this->getScore("TW2", mb_substr($sentence, $i - 2, 3, 'UTF-8'));
            }
            if ($i + 1 < $length) {
                $score += 2 * $this->getScore("TW3", mb_substr($sentence, $i - 1, 3, 'UTF-8'));
            }
            if ($i + 2 < $length) {
                $score += 2 * $this->getScore("TW4", mb_substr($sentence, $i, 3, 'UTF-8'));
            }
            if ($score > 0) {
                $result[] = '';
            }

            $result[array_key_last($result)] .= mb_substr($sentence, $i, 1, 'UTF-8');
        }

        assert(array_is_list($result));

        return $result;
    }
}
