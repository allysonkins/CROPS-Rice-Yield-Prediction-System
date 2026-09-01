<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RiceVariety;

class RiceVarietySeeder extends Seeder
{
    public function run()
    {
        // NSIC Inbred
        $inbred = [
            'NSIC Rc 104', 'NSIC Rc 106', 'NSIC Rc 108', 'NSIC Rc 110',
            'NSIC Rc 112', 'NSIC Rc 120', 'NSIC Rc 128', 'NSIC Rc 148',
            'NSIC Rc 150', 'NSIC Rc 152', 'NSIC Rc 154', 'NSIC Rc 156',
            'NSIC Rc 158', 'NSIC Rc 160', 'NSIC Rc 170', 'NSIC Rc 172',
            'NSIC Rc 182', 'NSIC Rc 184', 'NSIC Rc 186', 'NSIC Rc 188',
            'NSIC Rc 190', 'NSIC Rc 192', 'NSIC Rc 194', 'NSIC Rc 212',
            'NSIC Rc 214', 'NSIC Rc 216', 'NSIC Rc 218', 'NSIC Rc 220',
            'NSIC Rc 222', 'NSIC Rc 226', 'NSIC Rc 238', 'NSIC Rc 242',
            'NSIC Rc 272', 'NSIC Rc 274', 'NSIC Rc 276', 'NSIC Rc 278',
            'NSIC Rc 280', 'NSIC Rc 282', 'NSIC Rc 284', 'NSIC Rc 286',
            'NSIC Rc 288', 'NSIC Rc 298', 'NSIC Rc 300', 'NSIC Rc 302',
            'NSIC Rc 308', 'NSIC Rc 342', 'NSIC Rc 352', 'NSIC Rc 354',
            'NSIC Rc 358', 'NSIC Rc 400', 'NSIC Rc 402', 'NSIC Rc 426',
            'NSIC Rc 436', 'NSIC Rc 440', 'NSIC Rc 442', 'NSIC Rc 472',
            'NSIC Rc 478', 'NSIC Rc 480', 'NSIC Rc 482', 'NSIC Rc 484',
            'NSIC Rc 512', 'NSIC Rc 534', 'NSIC Rc 566', 'NSIC Rc 572',
            'NSIC Rc 590', 'NSIC Rc 600', 'NSIC Rc 622', 'NSIC Rc 626',
            'NSIC Rc 628', 'NSIC Rc 670', 'NSIC Rc 672', 'NSIC Rc 680',
            'NSIC Rc 684', 'NSIC Rc 686', 'NSIC Rc 732', 'NSIC Rc 736',
            'NSIC Rc 740', 'NSIC Rc 756'
        ];

        // NSIC Hybrid
        $hybrid = [
            'NSIC Rc 114H (Mestiso 2)',
            'NSIC Rc 116H (Mestiso 3)',
            'NSIC Rc 176H',
            'NSIC Rc 230H',
            'NSIC Rc 234H',
            'NSIC Rc 250H',
            'NSIC Rc 362J',
            'NSIC Rc 404H',
            'NSIC Rc 456H',
            'NSIC Rc 492H',
            'NSIC Rc 634H',
            'NSIC Rc 636H',
            'NSIC Rc 649H',
            'NSIC Rc 650H',
            'NSIC Rc 666H',
            'NSIC Rc 696H',
            'NSIC Rc 714H (Mestiso 132)',
            'NSIC Rc 742H'
        ];

        // Insert Inbred
        foreach ($inbred as $name) {
            RiceVariety::firstOrCreate(
                ['name' => $name],
                [
                    'classification'  => 'Inbred',
                    'growth_period'   => 110,
                    'resilience'      => json_encode(['Bacterial Blight', 'Tungro', 'Blast']),
                ]
            );
        }

        // Insert Hybrid
        foreach ($hybrid as $name) {
            RiceVariety::firstOrCreate(
                ['name' => $name],
                [
                    'classification'  => 'Hybrid',
                    'growth_period'   => 115,
                    'resilience'      => json_encode(['Bacterial Blight', 'Tungro', 'Blast']),
                ]
            );
        }

        $this->command->info('✅ Rice varieties seeded successfully!');
        $this->command->info('   Inbred: ' . count($inbred));
        $this->command->info('   Hybrid: ' . count($hybrid));
        $this->command->info('   TOTAL: ' . (count($inbred) + count($hybrid)) . ' varieties');
    }
}