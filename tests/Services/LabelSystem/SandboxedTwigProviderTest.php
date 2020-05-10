<?php
/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 - 2020 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Tests\Services\LabelSystem;

use App\Entity\LabelSystem\LabelOptions;
use App\Entity\Parts\Part;
use App\Entity\Parts\PartLot;
use App\Entity\Parts\Storelocation;
use App\Services\LabelSystem\Barcodes\BarcodeExampleElementsGenerator;
use App\Services\LabelSystem\SandboxedTwigProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Twig\Sandbox\SecurityError;

class SandboxedTwigProviderTest extends WebTestCase
{
    /** @var SandboxedTwigProvider */
    private $service;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = self::$container->get(SandboxedTwigProvider::class);
    }

    public function twigDataProvider(): array
    {
        return [
            [' {% for i in range(1, 3) %}
                    {{ part.id }}
                    {{ part.name }}
                    {{ part.lastModified | format_datetime }}
               {% endfor %}
            '],
            [' {% if part.category %}
                   {{ part.category }}
               {% endif %}
            '],
            [' {% set a = random(1, 3) %}
               {{ 1 + 2 | abs }}
               {{ "test" | capitalize | escape | lower | raw }}
               {{ "\n"  | nl2br | trim | title | url_encode | reverse }}
            '],
            ['
                {{ location.isRoot}} {{ location.isChildOf(location) }} {{ location.comment }} {{ location.level }}
                {{ location.fullPath }} {% set arr =  location.pathArray %} {% set child = location.children %} {{location.childrenNotSelectable}}
            '],
            ['
                {{ part.reviewNeeded }} {{ part.tags }} {{ part.mass }}
            ']
        ];
    }

    public function twigNotAllowedDataProvider(): array
    {
        return [
            ["{% block test %} {% endblock %}"],
            ["{% deprecated test %}"],
            ["{% flush %}"],
            ["{{ part.setName('test') }}"],
            ["{{ part.setCategory(null) }}"]
        ];
    }


    /**
     * @dataProvider twigDataProvider
     */
    public function testTwigFeatures(string $twig)
    {
        $options = new LabelOptions();
        $options->setSupportedElement('part');
        $options->setLines($twig);
        $options->setLinesMode('twig');

        $twig = $this->service->getTwig($options);
        $str = $twig->render('lines', [
            'part' => new Part(),
            'lot' => new PartLot(),
            'location' => new Storelocation(),
        ]);

        $this->assertIsString($str);
    }

    /**
     * @dataProvider twigNotAllowedDataProvider
     */
    public function testTwigForbidden(string $twig)
    {
        $this->expectException(SecurityError::class);

        $options = new LabelOptions();
        $options->setSupportedElement('part');
        $options->setLines($twig);
        $options->setLinesMode('twig');

        $twig = $this->service->getTwig($options);
        $str = $twig->render('lines', [
            'part' => new Part(),
            'lot' => new PartLot(),
            'location' => new Storelocation(),
        ]);
    }
}
