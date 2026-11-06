<?php

namespace Tests\Unit;

use App\Models\Printer;
use App\Models\Recipe;
use App\Models\RecipeField;
use App\Services\KeyenceCommunicationService;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;
use Tests\TestCase;

class KeyenceCommunicationServiceTest extends TestCase
{
    public function test_it_builds_the_ascii_message_command_documented_for_mk_g(): void
    {
        $service = new KeyenceCommunicationService();

        $this->assertSame('H2,1,2,LOT123', $service->buildMessageCommand(1, 2, 'LOT123'));
    }

    public function test_it_rejects_data_that_would_break_the_comma_delimited_protocol(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('virgole');

        (new KeyenceCommunicationService())->buildMessageCommand(1, 1, 'LOT,123');
    }

    public function test_it_encodes_stx_etx_with_the_documented_xor_checksum(): void
    {
        $printer = new Printer([
            'protocol_format' => 'stx_etx',
            'checksum_enabled' => true,
        ]);

        $this->assertSame("\x02FW,001,20\x03", (new KeyenceCommunicationService())->encode($printer, 'FW,001'));
    }

    public function test_it_builds_mk_g_message_layout_commands_from_recipe_fields(): void
    {
        $recipe = new Recipe(['program_number' => 12]);
        $recipe->setRelation('fields', new Collection([
            new RecipeField([
                'key' => 'lot',
                'default_value' => 'LOT-123',
                'printer_message_number' => 3,
                'printer_font' => 6,
                'layout_x' => 0,
                'layout_y' => 0,
            ]),
            new RecipeField([
                'key' => 'date',
                'printer_message_number' => 4,
                'printer_font' => 9,
                'layout_x' => 4095,
                'layout_y' => 31,
            ]),
        ]));

        $this->assertSame([
            'F1,012,03,01,06,0000,00,0000,00,1,0,00,0',
            'FS,012,03,0,LOT-123',
            'F1,012,04,01,09,4095,31,0000,00,1,0,00,0',
            'FS,012,04,0,[date]',
        ], (new KeyenceCommunicationService())->buildLayoutCommands($recipe));
    }
}
