<?php

namespace TrueCore\Tests\Traits;

trait Descriptions
{
    /** @TODO Перенести генерацию fake-данных в фабрики | 16.01.2020 */
    /**
     * @return array
     */
    protected function generateFakeDescriptions(): array
    {
        $name = $this->faker->unique()->word;
        $metaDescription = $this->faker->paragraph(2);
        $text = $this->faker->paragraph(10);
        $short = $this->faker->sentence(10);

        return [
            [
                'name'            => $name,
                'h1'              => $name,
                'title'           => $name,
                'text'            => $text,
                'short'           => $short,
                'metaDescription' => $metaDescription,
                'ogTitle'         => $name,
                'ogDescription'   => $metaDescription,
            ],
        ];
    }
}
