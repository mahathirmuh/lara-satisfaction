<?php

namespace Database\Seeders;

use App\Models\Organization\Department;
use Illuminate\Database\Seeder;

/**
 * The support service departments assessed by the survey and their sections
 * (service units). Employees choose one section per department to evaluate
 * and read its role overview first. Sample data: adjust the sections and
 * texts in Master Data.
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
            'code' => 'ENV',
            'name' => 'Environmental',
            'description' => 'Environmental permits, monitoring, waste management and rehabilitation of disturbed land.',
            'icon' => 'leaf',
            'color' => 'green',
            'sections' => [
                'Environmental Monitoring' => 'Environmental Monitoring measures air, water, noise and soil quality around the operation, maintains the environmental permits and reports compliance to the regulator, so every department can work within its permit conditions.',
                'Waste Management' => 'Waste Management organises the collection, segregation, storage and disposal of domestic, industrial and hazardous waste, and coaches departments on correct segregation at the source.',
                'Rehabilitation' => 'Rehabilitation plans and carries out the reclamation of disturbed land, runs the nursery and monitors revegetation success together with the mining and engineering teams.',
            ],
        ],
        [
            'code' => 'EXT',
            'name' => 'External Affairs',
            'description' => 'Asset protection, community relations and government relations.',
            'icon' => 'speakerphone',
            'color' => 'blue',
            'sections' => [
                'Asset Protection' => 'Security & Asset Protection secures the tangible and intangible assets of the company based on security risk management, considering both external and internal factors. PDCA is applied before, during and after every process, using three main components: people, technology and systems. The protection targets for managed risks are people, technology, systems/business processes and reputation.',
                'Community Relations' => 'Community Relations builds and maintains relationships with the surrounding villages, runs community development programmes, handles grievances and helps departments plan work that affects local communities.',
                'Government Relations' => 'Government Relations coordinates permits, licences and official correspondence with central and regional government agencies, and keeps departments informed of regulatory changes.',
            ],
        ],
        [
            'code' => 'HR',
            'name' => 'Human Resources',
            'description' => 'Recruitment, training and employee services.',
            'icon' => 'users',
            'color' => 'purple',
            'sections' => [
                'Recruitment' => 'Recruitment fills vacancies with qualified candidates, from manpower planning and sourcing to selection, onboarding and mobilisation to site.',
                'Training' => 'Training develops employee competence through inductions, mandatory certifications and development programmes based on the training needs of each department.',
                'Employee Services' => 'Employee Services handles payroll questions, benefits, leave, travel and accommodation, and keeps employee data and documents up to date.',
            ],
        ],
        [
            'code' => 'SCM',
            'name' => 'Supply Chain',
            'description' => 'Procurement, warehouse and logistics.',
            'icon' => 'truck',
            'color' => 'orange',
            'sections' => [
                'Procurement' => 'Procurement turns purchase requests into purchase orders and contracts, sources and evaluates vendors, and negotiates price, quality and delivery time.',
                'Warehouse' => 'Warehouse receives, stores and issues materials and spare parts, keeps stock records accurate and plans stock levels with the user departments.',
                'Logistics' => 'Logistics moves materials, equipment and people to and from site by land, sea and air, including customs clearance and freight tracking.',
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
