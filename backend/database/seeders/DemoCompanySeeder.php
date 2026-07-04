<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentLedgerEntry;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\User;
use App\Support\PaymentLedger;
use App\Support\RowLevelSecurity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoCompanySeeder extends Seeder
{
    private const USD_TO_NGN = 1500;

    public function run(): void
    {
        RowLevelSecurity::runWithContext([
            'app.user_id' => '1',
            'app.user_role' => 'admin',
            'app.access_mode' => 'authenticated',
            'app.public_access_token' => '',
        ], function () {
            DB::transaction(function () {
            $owner = User::query()->updateOrCreate(
                ['email' => 'tony@madeitcodes.online'],
                [
                    'name' => 'Tony Olugbusi',
                    'password' => Hash::make('TonyDemo123!'),
                    'role' => User::ROLE_OWNER,
                    'email_verified_at' => now(),
                    'trial_ends_at' => now()->addYears(2),
                    'google_id' => null,
                    'email_verification_token' => null,
                    'email_verification_sent_at' => null,
                ]
            );

            PaymentLedgerEntry::query()->where('user_id', $owner->id)->delete();
            Payment::query()->where('user_id', $owner->id)->delete();
            Invoice::query()->where('user_id', $owner->id)->delete();
            Contract::query()->where('user_id', $owner->id)->delete();
            Proposal::query()->where('user_id', $owner->id)->delete();
            Project::query()->where('user_id', $owner->id)->delete();
            Client::query()->where('user_id', $owner->id)->delete();
            Company::query()->where('user_id', $owner->id)->delete();

            $company = Company::query()->create([
                'user_id' => $owner->id,
                'name' => 'Acme Digital Solutions Ltd',
                'email' => 'hello@acmedigitalsolutions.com',
                'phone' => '+234 813 555 2048',
                'address' => '12 Admiralty Way, Lekki Phase 1, Lagos, Nigeria',
                'website' => 'https://madeitcodes.online',
                'tax_id' => 'RC-ACME-204812',
                'industry' => 'Software Development & Digital Consulting',
                'currency' => 'USD',
            ]);

            $clientSpecs = [
                ['company' => 'BrightTech Inc.', 'contact' => 'Sarah Johnson', 'email' => 'sarah.johnson@brighttechinc.com'],
                ['company' => 'Nova Retail Group', 'contact' => 'Michael Carter', 'email' => 'michael.carter@novaretailgroup.com'],
                ['company' => 'GreenBuild Construction', 'contact' => 'Emma Wilson', 'email' => 'emma.wilson@greenbuildconstruction.com'],
                ['company' => 'Horizon Logistics', 'contact' => 'David Brown', 'email' => 'david.brown@horizonlogistics.com'],
                ['company' => 'Apex Healthcare', 'contact' => 'Olivia Taylor', 'email' => 'olivia.taylor@apexhealthcare.com'],
                ['company' => 'Summit Financial Partners', 'contact' => 'Daniel Reed', 'email' => 'daniel.reed@summitfinancial.com'],
                ['company' => 'Bluewave Studios', 'contact' => 'Grace Morgan', 'email' => 'grace.morgan@bluewavestudios.com'],
                ['company' => 'Cedar Lane Properties', 'contact' => 'Aisha Bello', 'email' => 'aisha.bello@cedarlaneproperties.com'],
                ['company' => 'Prime Hospitality Group', 'contact' => 'Henry Adams', 'email' => 'henry.adams@primehospitalitygroup.com'],
                ['company' => 'Metro Energy Services', 'contact' => 'Nadia Ibrahim', 'email' => 'nadia.ibrahim@metroenergyservices.com'],
                ['company' => 'Orbit Education Systems', 'contact' => 'Peter Clark', 'email' => 'peter.clark@orbiteducation.com'],
                ['company' => 'Northstar Foods', 'contact' => 'Chinwe Okafor', 'email' => 'chinwe.okafor@northstarfoods.com'],
                ['company' => 'Verde Fashion House', 'contact' => 'Sophie Martin', 'email' => 'sophie.martin@verdefashionhouse.com'],
                ['company' => 'Atlas Manufacturing', 'contact' => 'Ahmed Yusuf', 'email' => 'ahmed.yusuf@atlasmanufacturing.com'],
                ['company' => 'Clearview Telecom', 'contact' => 'James Allen', 'email' => 'james.allen@clearviewtelecom.com'],
                ['company' => 'Pioneer Media Works', 'contact' => 'Laila Hassan', 'email' => 'laila.hassan@pioneermediaworks.com'],
                ['company' => 'Summit Health Labs', 'contact' => 'Victor Thompson', 'email' => 'victor.thompson@summithealthlabs.com'],
                ['company' => 'Trident Security Group', 'contact' => 'Mariam Sani', 'email' => 'mariam.sani@tridentsecurity.com'],
                ['company' => 'Eastpoint Marine', 'contact' => 'Jordan Lee', 'email' => 'jordan.lee@eastpointmarine.com'],
                ['company' => 'Crestline Travel', 'contact' => 'Rebecca Stone', 'email' => 'rebecca.stone@crestlinetravel.com'],
                ['company' => 'Harbor Design Co.', 'contact' => 'Felix Novak', 'email' => 'felix.novak@harbordesign.co'],
                ['company' => 'Nexa Services Ltd', 'contact' => 'Kemi Adegoke', 'email' => 'kemi.adegoke@nexaservices.com'],
                ['company' => 'Silverline Events', 'contact' => 'Musa Ibrahim', 'email' => 'musa.ibrahim@silverlineevents.com'],
                ['company' => 'Pulse HR Solutions', 'contact' => 'Hannah Brooks', 'email' => 'hannah.brooks@pulsehrsolutions.com'],
                ['company' => 'Vertex Supply Chain', 'contact' => 'Ifeoma Okeke', 'email' => 'ifeoma.okeke@vertexsupplychain.com'],
            ];

            $clients = [];
            foreach ($clientSpecs as $index => $spec) {
                $clients[] = Client::query()->create([
                    'user_id' => $owner->id,
                    'name' => $spec['contact'],
                    'email' => $spec['email'],
                    'phone' => $this->phoneForIndex($index),
                    'company' => $spec['company'],
                    'status' => 'Active',
                    'address' => $this->clientAddressForIndex($index),
                ]);
            }

            $projectSpecs = [
                ['title' => 'Company Website Redesign', 'status' => 'Active', 'client' => 'BrightTech Inc.', 'budget' => 18000000],
                ['title' => 'CRM Development', 'status' => 'Active', 'client' => 'Nova Retail Group', 'budget' => 24000000],
                ['title' => 'Mobile App Development', 'status' => 'Planning', 'client' => 'Apex Healthcare', 'budget' => 33000000],
                ['title' => 'ERP System', 'status' => 'Completed', 'client' => 'GreenBuild Construction', 'budget' => 46000000],
                ['title' => 'Membership Portal', 'status' => 'Active', 'client' => 'Horizon Logistics', 'budget' => 21000000],
                ['title' => 'Data Warehouse Upgrade', 'status' => 'On Hold', 'client' => 'Summit Financial Partners', 'budget' => 12000000],
                ['title' => 'Payment Gateway Integration', 'status' => 'Completed', 'client' => 'Prime Hospitality Group', 'budget' => 9400000],
                ['title' => 'Support Portal Refresh', 'status' => 'Active', 'client' => 'Bluewave Studios', 'budget' => 7600000],
                ['title' => 'Client Onboarding Automation', 'status' => 'Planning', 'client' => 'Cedar Lane Properties', 'budget' => 14800000],
                ['title' => 'Brand Microsite Launch', 'status' => 'Completed', 'client' => 'Northstar Foods', 'budget' => 6800000],
                ['title' => 'Analytics Dashboard', 'status' => 'Active', 'client' => 'Metro Energy Services', 'budget' => 11200000],
                ['title' => 'Cloud Migration Sprint', 'status' => 'Planning', 'client' => 'Orbit Education Systems', 'budget' => 19500000],
                ['title' => 'Internal Tools Revamp', 'status' => 'Completed', 'client' => 'Clearview Telecom', 'budget' => 8700000],
                ['title' => 'Lead Capture Engine', 'status' => 'Active', 'client' => 'Pulse HR Solutions', 'budget' => 6400000],
            ];

            $clientByCompany = collect($clients)->keyBy('company');

            foreach ($projectSpecs as $index => $spec) {
                Project::query()->create([
                    'user_id' => $owner->id,
                    'client_id' => $clientByCompany[$spec['client']]->id,
                    'title' => $spec['title'],
                    'description' => $spec['status'] === 'Planning'
                        ? 'Planned delivery with discovery, design, and implementation phases.'
                        : 'Active client delivery with milestone reviews and weekly status updates.',
                    'status' => $spec['status'],
                    'start_date' => now()->subDays(140 - ($index * 7))->toDateString(),
                    'end_date' => $spec['status'] === 'Completed'
                        ? now()->subDays(12 - $index)->toDateString()
                        : null,
                    'budget' => $spec['budget'],
                ]);
            }

            $proposalSpecs = [
                ['title' => 'Website redesign and content refresh', 'client' => 'BrightTech Inc.', 'status' => 'Approved', 'subtotal' => 7200000, 'tax' => 648000, 'total' => 7848000],
                ['title' => 'Retail CRM rollout', 'client' => 'Nova Retail Group', 'status' => 'Sent', 'subtotal' => 6200000, 'tax' => 558000, 'total' => 6758000],
                ['title' => 'Healthcare app discovery sprint', 'client' => 'Apex Healthcare', 'status' => 'Draft', 'subtotal' => 4100000, 'tax' => 369000, 'total' => 4469000],
                ['title' => 'Logistics integration roadmap', 'client' => 'Horizon Logistics', 'status' => 'Rejected', 'subtotal' => 3300000, 'tax' => 297000, 'total' => 3597000],
                ['title' => 'Membership portal support retainer', 'client' => 'Horizon Logistics', 'status' => 'Sent', 'subtotal' => 5400000, 'tax' => 486000, 'total' => 5886000],
            ];

            $proposals = [];
            foreach ($proposalSpecs as $index => $spec) {
                $proposal = Proposal::query()->create([
                    'user_id' => $owner->id,
                    'client_id' => $clientByCompany[$spec['client']]->id,
                    'title' => $spec['title'],
                    'status' => $spec['status'],
                    'issue_date' => now()->subDays(60 - ($index * 5))->toDateString(),
                    'expiry_date' => now()->addDays(20 - ($index * 2))->toDateString(),
                    'notes' => 'Prepared for the Acme Digital Solutions Ltd account to support ongoing delivery and renewal work.',
                    'subtotal' => $spec['subtotal'],
                    'tax' => $spec['tax'],
                    'total' => $spec['total'],
                ]);

                ProposalItem::query()->create([
                    'proposal_id' => $proposal->id,
                    'name' => 'Strategy and implementation',
                    'description' => 'Discovery, planning, and implementation support.',
                    'quantity' => 1,
                    'unit_price' => $spec['subtotal'],
                    'total' => $spec['subtotal'],
                ]);

                ProposalItem::query()->create([
                    'proposal_id' => $proposal->id,
                    'name' => 'Support and handover',
                    'description' => 'Launch support, documentation, and handoff.',
                    'quantity' => 1,
                    'unit_price' => 0,
                    'total' => 0,
                ]);

                $proposals[] = $proposal;
            }

            $contractSpecs = [
                ['title' => 'Master Services Agreement - BrightTech', 'client' => 'BrightTech Inc.', 'proposal' => 0, 'status' => 'Signed'],
                ['title' => 'Retainer Addendum - Nova Retail', 'client' => 'Nova Retail Group', 'proposal' => 1, 'status' => 'Sent'],
                ['title' => 'Implementation Agreement - Apex Healthcare', 'client' => 'Apex Healthcare', 'proposal' => 2, 'status' => 'Draft'],
                ['title' => 'Service Order - Horizon Logistics', 'client' => 'Horizon Logistics', 'proposal' => 3, 'status' => 'Signed'],
                ['title' => 'Portal Support Contract - Horizon Logistics', 'client' => 'Horizon Logistics', 'proposal' => 4, 'status' => 'Sent'],
            ];

            for ($i = 0; $i < 22; $i++) {
                $client = $clients[$i % count($clients)];
                $contractSpecs[] = [
                    'title' => sprintf('%s Agreement %02d', $client->company, $i + 1),
                    'client' => $client->company,
                    'proposal' => null,
                    'status' => match ($i % 3) {
                        0 => 'Draft',
                        1 => 'Sent',
                        default => 'Signed',
                    },
                ];
            }

            $contracts = [];
            foreach ($contractSpecs as $index => $spec) {
                $client = $clientByCompany[$spec['client']];
                $proposal = $spec['proposal'] !== null ? $proposals[$spec['proposal']] : null;
                $contract = Contract::query()->create([
                    'user_id' => $owner->id,
                    'client_id' => $client->id,
                    'proposal_id' => $proposal?->id,
                    'title' => $spec['title'],
                    'body_content' => $this->contractBody($client->company, $spec['title']),
                    'status' => $spec['status'],
                    'signing_token' => $spec['status'] === 'Sent' ? (string) Str::uuid() : null,
                    'client_signature_name' => $spec['status'] === 'Signed' ? $client->name : null,
                    'client_signature_ip' => $spec['status'] === 'Signed' ? '127.0.0.1' : null,
                    'client_signed_at' => $spec['status'] === 'Signed' ? now()->subDays(8 - ($index % 5)) : null,
                    'sent_at' => $spec['status'] !== 'Draft' ? now()->subDays(24 - $index) : null,
                    'signing_token_expires_at' => $spec['status'] === 'Sent' ? now()->addDays(21 - $index) : null,
                ]);

                $contracts[] = $contract;
            }

            $invoiceSpecs = [
                ['number' => 'INV-1001', 'usd' => 2500, 'client' => 'BrightTech Inc.', 'status' => 'Paid', 'contract' => 0],
                ['number' => 'INV-1002', 'usd' => 1800, 'client' => 'Nova Retail Group', 'status' => 'Sent', 'contract' => 1],
                ['number' => 'INV-1003', 'usd' => 4700, 'client' => 'GreenBuild Construction', 'status' => 'Overdue', 'contract' => 2],
                ['number' => 'INV-1004', 'usd' => 980, 'client' => 'Horizon Logistics', 'status' => 'Draft', 'contract' => 3],
                ['number' => 'INV-1005', 'usd' => 6200, 'client' => 'Apex Healthcare', 'status' => 'Paid', 'contract' => 4],
            ];

            for ($i = 0; $i < 33; $i++) {
                $client = $clients[$i % count($clients)];
                $usd = [1200, 1550, 2300, 3100, 4200, 5150, 6750, 860, 1940, 2880, 3460, 4980, 6120, 7250, 810, 930, 1420, 2700, 3950, 5600, 6400, 770, 1180, 1660, 2450, 3320, 4750, 5890, 7200, 880, 990, 1525, 2675][$i];
                $status = match ($i % 4) {
                    0 => 'Sent',
                    1 => 'Paid',
                    2 => 'Overdue',
                    default => 'Draft',
                };

                $invoiceSpecs[] = [
                    'number' => sprintf('INV-%04d', 1006 + $i),
                    'usd' => $usd,
                    'client' => $client->company,
                    'status' => $status,
                    'contract' => $i % count($contracts),
                ];
            }

            $invoices = [];
            foreach ($invoiceSpecs as $index => $spec) {
                $client = $clientByCompany[$spec['client']];
                $total = $this->nairaFromUsd($spec['usd']);
                $invoice = Invoice::query()->create([
                    'user_id' => $owner->id,
                    'company_id' => $company->id,
                    'client_id' => $client->id,
                    'contract_id' => $contracts[$spec['contract']]?->id,
                    'invoice_number' => $spec['number'],
                    'status' => $spec['status'],
                    'sent_at' => in_array($spec['status'], ['Sent', 'Paid', 'Overdue'], true) ? now()->subDays(28 - $index) : null,
                    'public_payment_token' => in_array($spec['status'], ['Sent', 'Overdue'], true) ? Str::random(40) : null,
                    'public_payment_token_expires_at' => in_array($spec['status'], ['Sent', 'Overdue'], true) ? now()->addDays(14 - ($index % 7)) : null,
                    'issue_date' => now()->subDays(45 - $index)->toDateString(),
                    'due_date' => now()->subDays($spec['status'] === 'Overdue' ? 8 : -14 + $index % 5)->toDateString(),
                    'notes' => 'Demo invoice seeded for Acme Digital Solutions Ltd. Amount converted from USD to NGN at 1 USD = NGN 1,500.',
                    'subtotal' => $total,
                    'tax' => 0,
                    'discount' => 0,
                    'total' => $total,
                ]);

                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'name' => $this->invoiceLineName($spec['number']),
                    'description' => 'Design, development, deployment, and client support package.',
                    'quantity' => 1,
                    'unit_price' => $total,
                    'total' => $total,
                ]);

                $invoices[] = $invoice;
            }

            $paymentSpecs = [];
            $completedInvoices = collect($invoices)->where('status', 'Paid')->values();
            $sentInvoices = collect($invoices)->where('status', 'Sent')->values();
            $overdueInvoices = collect($invoices)->where('status', 'Overdue')->values();

            foreach ($completedInvoices->take(12) as $index => $invoice) {
                $paymentSpecs[] = [
                    'invoice' => $invoice,
                    'status' => 'Completed',
                    'gateway' => $index % 2 === 0 ? 'Paystack' : 'Flutterwave',
                    'event_type' => 'captured',
                    'amount' => (float) $invoice->total,
                ];
            }

            foreach ($sentInvoices->take(8) as $index => $invoice) {
                $paymentSpecs[] = [
                    'invoice' => $invoice,
                    'status' => 'Pending',
                    'gateway' => $index % 2 === 0 ? 'Manual' : 'Paystack',
                    'event_type' => 'processing',
                    'amount' => (float) $invoice->total,
                ];
            }

            foreach ($overdueInvoices->take(4) as $index => $invoice) {
                $paymentSpecs[] = [
                    'invoice' => $invoice,
                    'status' => 'Failed',
                    'gateway' => $index % 2 === 0 ? 'Flutterwave' : 'Paystack',
                    'event_type' => 'failed',
                    'amount' => (float) $invoice->total,
                ];
            }

            foreach ($completedInvoices->slice(12)->take(3) as $index => $invoice) {
                $paymentSpecs[] = [
                    'invoice' => $invoice,
                    'status' => 'Refunded',
                    'gateway' => 'Manual',
                    'event_type' => 'refunded',
                    'amount' => (float) $invoice->total,
                ];
            }

            foreach ($paymentSpecs as $index => $spec) {
                $payment = Payment::query()->create([
                    'user_id' => $owner->id,
                    'invoice_id' => $spec['invoice']->id,
                    'client_id' => $spec['invoice']->client_id,
                    'amount' => $spec['amount'],
                    'currency' => 'NGN',
                    'status' => $spec['status'],
                    'gateway' => $spec['gateway'],
                    'gateway_reference' => sprintf('BL-DEMO-%05d', $index + 1),
                    'gateway_transaction_id' => sprintf('txn_%05d', $index + 1),
                    'notes' => 'Demo payment seeded for dashboard activity.',
                    'paid_at' => in_array($spec['status'], ['Completed', 'Refunded'], true) ? now()->subDays(18 - $index) : null,
                ]);

                app(PaymentLedger::class)->append([
                    'user_id' => $owner->id,
                    'payment_id' => $payment->id,
                    'invoice_id' => $spec['invoice']->id,
                    'gateway' => $spec['gateway'],
                    'event_type' => $spec['event_type'],
                    'gateway_reference' => $payment->gateway_reference,
                    'dedupe_key' => sprintf('demo-payment-ledger-%05d', $index + 1),
                    'amount' => $spec['amount'],
                    'currency' => 'NGN',
                    'occurred_at' => now()->subDays(18 - $index),
                    'payload' => [
                        'demo_seed' => true,
                        'invoice_number' => $spec['invoice']->invoice_number,
                    ],
                ]);
            }
            });
        });
    }

    private function nairaFromUsd(float|int $usd): float
    {
        return (float) ($usd * self::USD_TO_NGN);
    }

    private function phoneForIndex(int $index): string
    {
        $prefixes = ['+1 312', '+44 20', '+234 803', '+234 812', '+27 11'];
        $prefix = $prefixes[$index % count($prefixes)];

        return sprintf('%s %03d %04d', $prefix, 100 + $index, 2000 + $index);
    }

    private function clientAddressForIndex(int $index): string
    {
        $locations = [
            'London, United Kingdom',
            'Chicago, Illinois, USA',
            'Lagos, Nigeria',
            'Toronto, Ontario, Canada',
            'Dubai, UAE',
        ];

        return sprintf('%s Office %d', $locations[$index % count($locations)], $index + 1);
    }

    private function contractBody(string $company, string $title): string
    {
        return <<<TEXT
This agreement covers the delivery of {$title} for {$company}.

Scope includes planning, implementation, review cycles, and handover support.
TEXT;
    }

    private function invoiceLineName(string $invoiceNumber): string
    {
        return match ($invoiceNumber) {
            'INV-1001' => 'Website redesign and content refresh',
            'INV-1002' => 'CRM development milestone',
            'INV-1003' => 'Mobile app delivery sprint',
            'INV-1004' => 'Discovery and planning session',
            'INV-1005' => 'Membership portal implementation',
            default => 'Consulting and development services',
        };
    }
}
