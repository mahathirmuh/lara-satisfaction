<?php

namespace Database\Seeders;

use App\Models\Organization\Department;
use Illuminate\Database\Seeder;

/**
 * The support service departments assessed by the survey: the departments
 * under the Support Services Deputy General Manager in the MTI organization
 * chart 2026, in chart order. Their sections are the units on the supervisor
 * level of each department's chart. Employees evaluate every section and read
 * its role overview first; the role overviews are short starting texts to
 * complete in Master Data.
 */
class DepartmentSeeder extends Seeder
{
    /**
     * Departments with their sections (section name => role overview).
     *
     * @var array<int, array{code: string, name: string, description: string, icon: string, color: string, sections: array<string, string>}>
     */
    public const DEPARTMENTS = [
        [
            'code' => 'SCM',
            'name' => 'Supply Chain',
            'description' => 'Logistic & distribution, heavy equipment, inventory and expediting.',
            'icon' => 'truck',
            'color' => 'orange',
            'sections' => [
                'Logistic & Distribution' => 'Logistic & Distribution manages logistics administration and the distribution of materials, with warehouse and logistics assistants handling the goods on site.',
                'Heavy Equipment' => 'Heavy Equipment operates the transport and lifting equipment, such as cranes and forklifts, used to move materials on site.',
                'Inventory & Expeditor' => 'Inventory & Expeditor keeps the inventory under control, expedites orders and deliveries, and manages the fuel distribution.',
            ],
        ],
        [
            'code' => 'OHS',
            'name' => 'Occupational Health & Safety',
            'description' => 'Emergency response, plant safety, OHS system and training, occupational health, industrial hygiene and process safety.',
            'icon' => 'shield-check',
            'color' => 'red',
            'sections' => [
                'Emergency Response' => 'Emergency Response prepares for and responds to emergencies in all plants.',
                'Plant OHS' => 'Plant OHS supervises occupational health and safety in the Acid, Chloride, Copper Cathode and Pyrite plants.',
                'OHS System & Reporting' => 'OHS System & Reporting maintains the OHS management system and the OHS reporting.',
                'OHS Training' => 'OHS Training plans and delivers the OHS training for employees.',
                'OHS Support & Fleet' => 'OHS Support & Fleet supports the OHS activities and the safety of the fleet.',
                'Occupational Health (Paramedic)' => 'Occupational Health provides the paramedic and nursing services, led by the company doctor.',
                'Industrial Hygiene' => 'Industrial Hygiene identifies and monitors health hazards in the workplace.',
                'Process Safety' => 'Process Safety supports the plants with process safety engineering.',
            ],
        ],
        [
            'code' => 'ENV',
            'name' => 'Environmental',
            'description' => 'Environmental monitoring, waste management, environmental system, studies and projects.',
            'icon' => 'leaf',
            'color' => 'green',
            'sections' => [
                'Environmental Monitoring' => 'Environmental Monitoring monitors the air and water quality around the operation.',
                'Waste Management' => 'Waste Management handles the hazardous and non-hazardous waste.',
                'Environmental System' => 'Environmental System maintains the environmental management system.',
                'Study & Project' => 'Study & Project carries out the environmental studies and projects.',
            ],
        ],
        [
            'code' => 'EXT',
            'name' => 'External Affairs',
            'description' => 'Community relations, compliance & corporate communications and asset protection.',
            'icon' => 'speakerphone',
            'color' => 'blue',
            'sections' => [
                'Community Relations' => 'Community Relations maintains the relations with the surrounding communities.',
                'Compliance & Corporate Communications' => 'Compliance & Corporate Communications handles compliance matters and the corporate communications.',
                'Asset Protection' => 'Security & Asset Protection secures the tangible and intangible assets of the company based on security risk management, considering both external and internal factors. PDCA is applied before, during and after every process, using three main components: people, technology and systems. The protection targets for managed risks are people, technology, systems/business processes and reputation.',
            ],
        ],
        [
            'code' => 'FIN',
            'name' => 'Finance',
            'description' => 'Sales support, cost control, finance and financial analysis & forecasting.',
            'icon' => 'report-money',
            'color' => 'purple',
            'sections' => [
                'Sales Support' => 'Sales Support provides the administrative support for sales.',
                'Cost Control' => 'Cost Control monitors and controls the costs against the budget.',
                'Finance' => 'Finance handles the day-to-day finance operations.',
                'Finance Analysis & Forecasting' => 'Finance Analysis & Forecasting prepares the financial analyses and forecasts.',
            ],
        ],
        [
            'code' => 'INTF',
            'name' => 'Interface',
            'description' => 'Interface management and contract management.',
            'icon' => 'arrows-exchange',
            'color' => 'yellow',
            'sections' => [
                'Interface Management' => 'Interface Management coordinates the interfaces between the parties involved in the operation.',
                'Contract Management' => 'Contract Management administers the contracts and follows up their execution.',
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::DEPARTMENTS as $order => $attributes) {
            $sections = $attributes['sections'];
            unset($attributes['sections']);

            $department = Department::query()->updateOrCreate(
                ['code' => $attributes['code']],
                [...$attributes, 'sort_order' => $order + 1, 'is_active' => true],
            );

            foreach (array_keys($sections) as $sectionOrder => $name) {
                $department->sections()->updateOrCreate(
                    ['name' => $name],
                    ['description' => $sections[$name], 'sort_order' => $sectionOrder + 1, 'is_active' => true],
                );
            }
        }
    }
}
