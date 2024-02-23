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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function compact;
use function fgetcsv;
use function strtr;

#[CoversClass(Parser::class)]
class QualityTest extends TestCase
{
    private const SEP = '▁';

    /**
     * @param list<string> $expected
     */
    #[DataProvider('jaProvider')]
    public function testJa(string $label, string $input, array $expected): void
    {
        $parser = Parser::loadDefaultJapaneseParser();

        $this->assertEquals(
            $expected,
            actual: $parser->parse($input),
        );
    }

    /**
     * @return iterable<array{label: string, input: string, expected: list<string>}>
     */
    public static function jaProvider(): iterable
    {
        $fp = fopen(__DIR__ . '/../../tests/quality/ja.tsv', 'r');
        assert($fp !== false);
        $header = fgets($fp);
        assert($header === "# label	sentence\n");

        while ([$label, $data] = fgetcsv($fp, 1024, "\t")) {
            $input = strtr($data, [self::SEP => '']);
            $expected = explode(self::SEP, $data);
            yield compact('label', 'input', 'expected');
        }
    }
}
