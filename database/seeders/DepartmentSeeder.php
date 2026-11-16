<?php

namespace Database\Seeders;

use App\Models\Organization\Department;
use Illuminate\Database\Seeder;

/**
 * The support service departments assessed by the survey: the departments
 * under the Support Services Deputy General Manager in the MTI organization
 * chart 2026, in chart order, then Human Resources (HR, General Services and
 * ICT under the HRGS & IT Manager), which the "NOW" chart also places under
 * Support Services. Their sections are the units on the supervisor level of
 * each department's chart. Employees evaluate every section and read its
 * role overview first; the role overviews are short starting texts to
 * complete in Master Data.
 *
 * Initial data only: once departments exist, Master Data is the source of
 * truth and running the seeders again leaves it untouched.
 */
class DepartmentSeeder extends Seeder
{
    /**
     * Departments with the job titles of their managers (who see the
     * department's results) and their sections (section name => role overview).
     *
     * @var array<int, array{code: string, name: string, description: string, icon: string, color: string, manager_job_titles: array<int, string>, directory_names: array<int, string>, sections: array<string, string>}>
     */
    public const DEPARTMENTS = [
        [
            'code' => 'SCM',
            'name' => 'Supply Chain',
            'description' => 'Logistic & distribution, heavy equipment, inventory and expediting.',
            'icon' => 'truck',
            'color' => 'orange',
            'manager_job_titles' => ['Supply Chain*Manager'],
            'directory_names' => ['Supply Chain Management'],
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
            'manager_job_titles' => ['OHS Manager', 'Occupational Health & Safety Manager'],
            'directory_names' => ['Occupational Health and Safety'],
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
            'manager_job_titles' => ['Environmental Manager', 'Environment Manager'],
            'directory_names' => ['Environment'],
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
            'manager_job_titles' => ['External Affair* Manager'],
            'directory_names' => ['External Affair'],
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
            'manager_job_titles' => ['Finance Manager'],
            'directory_names' => ['Finance'],
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
            'manager_job_titles' => ['Interface Manager'],
            'directory_names' => ['Interface'],
            'sections' => [
                'Interface Management' => 'Interface Management coordinates the interfaces between the parties involved in the operation.',
                'Contract Management' => 'Contract Management administers the contracts and follows up their execution.',
            ],
        ],
        [
            'code' => 'HR',
            'name' => 'Human Resources',
            'description' => 'HR operation, people development, rewards & recruitment, general services (dormitory, catering, transport, facilities), translation and ICT.',
            'icon' => 'users',
            'color' => 'pink',
            'manager_job_titles' => ['HR Manager', 'Human Resource* Manager', 'HRGS & IT Manager'],
            'directory_names' => ['Human Resources'],
            'sections' => [
                'IT System & Support' => 'IT System & Support runs the business applications and helps users with their computers, accounts and applications.',
                'IT Infrastructure & Security' => 'IT Infrastructure & Security runs the network, servers and communication systems and keeps them secure.',
                'ER/IR & Formalities' => 'ER/IR & Formalities looks after employee and industrial relations and the formalities of employees, such as permits and official documents.',
                'HR Operation' => 'HR Operation runs the daily HR administration: attendance, leave, payroll data and employee records.',
                'People & Organization Development' => 'People & Organization Development develops employees through training and career programs and maintains the organization structure.',
                'Dormitory & Catering' => 'Dormitory & Catering manages the employee dormitories and the catering services.',
                'General Admin & Transport' => 'General Admin & Transport handles the general administration and the employee transport.',
                'Asset & Facility' => 'Asset & Facility maintains the offices and facilities and manages the general assets.',
                'Rewards & Recruitment' => 'Rewards & Recruitment recruits new employees and manages compensation and benefits.',
                'Translator' => 'Translator provides Mandarin and Indonesian translation and interpretation for meetings and documents.',
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Department::query()->exists()) {
            $this->command?->info('Departments already exist, skipping (manage them in Master Data).');

            return;
        }

        foreach (self::DEPARTMENTS as $order => $attributes) {
            $sections = $attributes['sections'];
            unset($attributes['sections']);

            $department = Department::create([...$attributes, 'sort_order' => $order + 1, 'is_active' => true]);

            foreach (array_keys($sections) as $sectionOrder => $name) {
                $department->sections()->create([
                    'name' => $name,
                    'description' => $sections[$name],
                    'sort_order' => $sectionOrder + 1,
                    'is_active' => true,
                ]);
            }
        }
    }
}
