<?php

namespace Tests\Unit;

use App\Services\RecipeFieldKeyGenerator;
use Tests\TestCase;

class RecipeFieldKeyGeneratorTest extends TestCase
{
    public function test_it_generates_unique_ascii_keys_from_labels(): void
    {
        $generator = new RecipeFieldKeyGenerator();

        $this->assertSame('numero_lotto', $generator->generate('Numero lotto', []));
        $this->assertSame('numero_lotto_2', $generator->generate('Numero lotto', ['numero_lotto']));
        $this->assertSame('campo_123', $generator->generate('123', []));
        $this->assertSame('campo', $generator->generate('!!!', []));
    }
}
