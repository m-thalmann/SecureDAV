<?php

namespace Tests\Unit\Rules;

use App\Rules\NotStringContains;
use PHPUnit\Framework\TestCase;

class NotStringContainsTest extends TestCase {
    public function testValidationSucceedsIfNoIllegalCharactersArePresent(): void {
        $rule = new NotStringContains(['#', '%', '&']);

        $this->assertNull($this->validate($rule, 'test'));
    }

    /**
     * @dataProvider failValidationProvider
     */
    public function testValidationFailsIfIllegalCharactersArePresent(
        string $string,
        string $expectedCharacter
    ): void {
        $rule = new NotStringContains(['#', '%', '&']);

        $this->validate($rule, $string, $expectedCharacter);
    }

    protected function validate(
        NotStringContains $rule,
        string $value,
        ?string $expectedCharacter = null
    ): void {
        $failCharacter = null;

        $fail = function (string $message) use (&$failCharacter) {
            return new class ($failCharacter) {
                public function __construct(protected ?string &$failCharacter) {
                }

                public function translate(array $replacements): void {
                    $this->failCharacter = $replacements['char'];
                }
            };
        };

        $rule->validate('attribute', $value, $fail);

        $this->assertSame($expectedCharacter, $failCharacter);
    }

    public static function failValidationProvider(): array {
        return [['t#est%', '#'], ['&test', '&'], ['test%', '%']];
    }
}
