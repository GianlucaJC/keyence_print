<?php

namespace Tests\Unit;

use App\Models\Printer;
use App\Services\KeyenceCommunicationService;
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
}
