<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MockupsSubmission;
use App\Models\User;
use Carbon\Carbon;

class MockupsSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user for created_by
        $user = User::first();
        if (!$user) {
            $this->command->warn('No users found. Please create a user first.');
            return;
        }

        // Sample mockup submissions
        $submissions = [
            [
                'title' => 'New Merch #1759448693',
                'customer_name' => 'Will Hunt',
                'customer_email' => 'will@ethos.community',
                'customer_phone' => '(858) 923-9064',
                'website' => 'www.ethos.community',
                'company_name' => 'Ethos',
                'instagram' => '@ethos_co',
                'notes' => 'Hi. Here are some new designs that i want on some of the products. Ive ordered some products that i selected before but this time is with a new design. Im curios if one of the designs i made will come out in high quality for print since it was pretty hard to create its the "built different" design. Let me know. Im open to suggestions. Also if everything looks good i would also want them on my shop. Thank you for everything you guys do.',
                'products' => [
                    [
                        'product_name' => 'The Crop Muscle Tank',
                        'style' => 'Black',
                        'color' => 'Black',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Crop Muscle Tank',
                        'style' => 'Storm',
                        'color' => 'Storm',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Mens Hoodie',
                        'style' => 'Black',
                        'color' => 'Black',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Basic Legging',
                        'style' => 'Black',
                        'color' => 'Black',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Basic Bra',
                        'style' => 'Black',
                        'color' => 'Black',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Basic Short',
                        'style' => 'Black',
                        'color' => 'Black',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Mens Tee',
                        'style' => 'Bone',
                        'color' => 'Bone',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Mens Tee',
                        'style' => 'Black',
                        'color' => 'Black',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Mens Heavyweight Tee',
                        'style' => 'Black',
                        'color' => 'Black',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Mens Heavyweight Tee',
                        'style' => 'Navy',
                        'color' => 'Navy',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Mens Heavyweight Tee',
                        'style' => 'Off White',
                        'color' => 'Off White',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Womens Hoodie',
                        'style' => 'Black',
                        'color' => 'Black',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Kids Hoodie',
                        'style' => 'Black',
                        'color' => 'Black',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Canvas Cap',
                        'style' => 'Natural / Black',
                        'color' => 'Natural / Black',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                ],
                'graphics' => [],
                'pdfs' => [],
                'created_at' => Carbon::now()->subDays(5),
            ],
            [
                'title' => 'Merch #1763843478',
                'customer_name' => 'Meryem Barbenes',
                'customer_email' => 'meryem.barbenes@gmail.com',
                'customer_phone' => '9177212231',
                'website' => 'www.bloompilatesstudio.com',
                'company_name' => 'Bloom Pilates LLC',
                'instagram' => 'bloompilateshoboken',
                'notes' => null,
                'products' => [
                    [
                        'product_name' => 'The Recycled Unisex Crewneck',
                        'style' => 'Heather',
                        'color' => 'Heather',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                ],
                'graphics' => [],
                'pdfs' => [],
                'created_at' => Carbon::now()->subDays(3),
            ],
            [
                'title' => 'New Merch Collection',
                'customer_name' => 'Ashlee Fiorini',
                'customer_email' => 'ashlee@pulselagreefitness.com',
                'customer_phone' => '3155466816',
                'website' => 'pulselagreefitness.com',
                'company_name' => 'Pulse Lagree Fitness',
                'instagram' => 'pulselagree.syr',
                'notes' => 'Heart Logo with "pulse lagree": Desired placement is either in the center of a tank (in orange color) or on the side of grip socks. White Tank: Should feature an orange lightning bolt in its center. Crew Neck: Should have "PULSE" in large writing, with "lagree" in smaller lettering underneath it. Additionally, an orange lightning bolt should be placed by the left top cuff of the sleeve.',
                'products' => [
                    [
                        'product_name' => 'The Midweight Unisex Crewneck',
                        'style' => 'Washed Maroon',
                        'color' => 'Maroon',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Midweight Unisex Crewneck',
                        'style' => 'Washed Ivory',
                        'color' => 'Ivory',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Midweight Unisex Crewneck',
                        'style' => 'Washed Amber',
                        'color' => 'Amber',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Ribbed Crop Tank',
                        'style' => 'White',
                        'color' => 'White',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Micro Rib Crop Long Sleeve',
                        'style' => 'Heather',
                        'color' => 'Heather',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Micro Rib Crop Long Sleeve',
                        'style' => 'Natural',
                        'color' => 'Natural',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Micro Rib Crop Long Sleeve',
                        'style' => 'White',
                        'color' => 'White',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Womens 1/4 Zip',
                        'style' => 'Off White',
                        'color' => 'Off White',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Straight Leg Sweatpant',
                        'style' => 'Bone',
                        'color' => 'Bone',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Womens Sweatpants',
                        'style' => 'Bone',
                        'color' => 'Bone',
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The 3/4 Crew Sock',
                        'style' => null,
                        'color' => null,
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                    [
                        'product_name' => 'The Ankle Sock',
                        'style' => null,
                        'color' => null,
                        'product_pdf' => null,
                        'status' => null,
                        'adjustment_notes' => null,
                    ],
                ],
                'graphics' => [],
                'pdfs' => [],
                'created_at' => Carbon::now()->subDays(1),
            ],
        ];

        // Create submissions with tracking numbers
        $maxTrackingNumber = MockupsSubmission::max('tracking_number') ?? 0;

        foreach ($submissions as $index => $submission) {
            $trackingNumber = $maxTrackingNumber + $index + 1;
            
            MockupsSubmission::create([
                'title' => $submission['title'],
                'tracking_number' => $trackingNumber,
                'customer_name' => $submission['customer_name'],
                'customer_email' => $submission['customer_email'],
                'customer_phone' => $submission['customer_phone'],
                'website' => $submission['website'],
                'company_name' => $submission['company_name'],
                'instagram' => $submission['instagram'],
                'notes' => $submission['notes'],
                'products' => $submission['products'],
                'graphics' => $submission['graphics'],
                'pdfs' => $submission['pdfs'],
                'created_by' => $user->id,
                'is_completed' => false,
                'created_at' => $submission['created_at'],
                'updated_at' => $submission['created_at'],
            ]);
        }

        $this->command->info('Sample mockup submissions created successfully!');
    }
}
