<?php

namespace Apps\Tms\Packages\Employees\Model;

use System\Base\BaseModel;
use System\Base\Providers\BasepackagesServiceProvider\Packages\Model\BasepackagesAddressBook;
use System\Base\Providers\BasepackagesServiceProvider\Packages\Model\Users\BasepackagesUsersAccounts;
use System\Base\Providers\BasepackagesServiceProvider\Packages\Model\Users\BasepackagesUsersProfiles;

class AppsTmsEmployees extends BaseModel
{
    protected $modelRelations = [];

    public $id;

    public $organisation_id;

    public $account_id;

    public $employee_id;

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
        $this->modelRelations['account']['relationObj'] = $this->hasOne(
            'account_id',
            BasepackagesUsersAccounts::class,
            'id',
            [
                'alias'                 => 'account'
            ]
        );

        $this->modelRelations['profile']['relationObj'] = $this->hasOneThrough(
            'account_id',
            BasepackagesUsersAccounts::class,
            'id',
            'id',
            BasepackagesUsersProfiles::class,
            'account_id',
            [
                'alias'         => 'profile'
            ]
        );

        $this->modelRelations['addresses']['relationObj'] = $this->hasMany(
            'id',
            BasepackagesAddressBook::class,
            'package_row_id',
            [
                'alias'                 => 'addresses',
                'params'                => [
                    'conditions'        => 'package_name = :package_name:',
                    'bind'              => [
                        'package_name'  => 'Companies'
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