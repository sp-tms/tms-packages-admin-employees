<?php

namespace Apps\Tms\Packages\Employees\Model;

use Apps\Tms\Packages\Employees\Employees;
use System\Base\BaseModel;
use System\Base\Providers\BasepackagesServiceProvider\Packages\Model\BasepackagesAddressBook;
use System\Base\Providers\BasepackagesServiceProvider\Packages\Model\BasepackagesContactBook;

class AppsTmsEmployees extends BaseModel
{
    protected $modelRelations = [];

    public $id;

    public $portrait;

    public $organisation_id;

    public $account_id;

    public $employee_id;

    public $status;

    public $designation;

    public $manager_id;

    public $drivers_license;

    public $drivers_license_valid_till;

    public $hazardous_license;

    public $hazardous_license_valid_till;

    public $adhar_card;

    public $pan_card;

    public function initialize()
    {
        $this->modelRelations['addresses']['relationObj'] = $this->hasMany(
            'id',
            BasepackagesAddressBook::class,
            'package_row_id',
            [
                'alias'                 => 'addresses',
                'params'                => [
                    'conditions'        => 'package_class = :package_class:',
                    'bind'              => [
                        'package_class'  => str_replace('\\', '_', Employees::class)
                    ]
                ]
            ]
        );

        $this->modelRelations['contact']['relationObj'] = $this->hasOne(
            'id',
            BasepackagesContactBook::class,
            'package_row_id',
            [
                'alias'                 => 'contact',
                'params'                => [
                    'conditions'        => 'package_class = :package_class:',
                    'bind'              => [
                        'package_class'  => str_replace('\\', '_', Employees::class)
                    ]
                ]
            ]
        );

        parent::initialize();
    }

    public function getModelRelations()
    {
        if (count($this->modelRelations) === 0) {
            $this->initialize();
        }

        return $this->modelRelations;
    }
}