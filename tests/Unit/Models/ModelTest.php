<?php

namespace Tests\Unit\Models;

use Illuminate\Support\Str;
use Tests\TestCase;

class ModelTest extends TestCase {
    public function testModelsHaveCorrectDateFormat(): void {
        $models = collect(scandir(app_path('Models')))
            ->filter(function (string $path) {
                if (in_array($path, ['.', '..', 'Pivots'])) {
                    return false;
                }

                if (!Str::endsWith($path, '.php')) {
                    $this->fail(
                        "Unexpected file or directory found in the app/Models directory: $path"
                    );
                }

                return true;
            })
            ->map(
                fn(string $path) => 'App\\Models\\' . Str::before($path, '.php')
            )
            ->all();

        $pivots = collect(scandir(app_path('Models/Pivots')))
            ->filter(function (string $path) {
                if (in_array($path, ['.', '..'])) {
                    return false;
                }

                if (!Str::endsWith($path, '.php')) {
                    $this->fail(
                        "Unexpected file or directory found in the app/Models/Pivots directory: $path"
                    );
                }

                return true;
            })
            ->map(
                fn(string $path) => 'App\\Models\\Pivots\\' .
                    Str::before($path, '.php')
            )
            ->all();

        $classes = array_merge($models, $pivots);

        foreach ($classes as $class) {
            $model = new $class();

            $this->assertEquals(
                'c',
                $model->getDateFormat(),
                "The $class model should have a date format of 'c'"
            );
        }
    }
}
