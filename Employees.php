<?php

namespace Apps\Tms\Packages\Employees;

use Apps\Tms\Packages\Employees\Employees;
use Apps\Tms\Packages\Employees\Model\AppsTmsEmployees;
use System\Base\BasePackage;
use System\Base\Providers\BasepackagesServiceProvider\Packages\Model\Users\Accounts\BasepackagesUsersAccountsSecurity;

class Employees extends BasePackage
{
    protected $modelToUse = AppsTmsEmployees::class;

    protected $packageName = 'employees';

    public $employees;

    public function init()
    {
        parent::init();

        return $this;
    }

    public function getEmployees($employeeId)
    {
        if ($this->config->databasetype === 'db') {
            $employeesObj = $this->getFirst('id', $employeeId);

            if ($employeesObj) {
                $employee = $employeesObj->toArray();

                $addressObj = $employeesObj->getAddresses();

                $employee['address'] = [];

                if ($addressObj) {
                    $employee['address'] = $addressObj->toArray();
                }
            }
        } else {
            $this->setFFRelations(true);
            $this->setFFRelationsConditions(
                [
                    'addresses' => ['package_class', '=', str_replace('\\', '_', Employees::class)],
                    'contact' => ['package_class', '=', str_replace('\\', '_', Employees::class)]
                ]
            );

            $employee = $this->getFirst('id', $employeeId, false, true, null, [], true);
        }

        if (isset($employee)) {
            $this->addResponse('Employee', 0, ['employee' => $employee]);

            return $employee;
        }

        $this->addResponse('No employee found with the ID provided', 1, []);

        return false;
    }

    public function addEmployee($data)
    {
        $this->checkDesignation($data);

        if ($this->add($data)) {
            $employee = $this->getEmployees($this->packagesData->last['id']);

            //Generate User account in the system (if email is provided)
            $newAccount = [];
            $newAccount['status'] = false;
            $newAccount['email'] = $data['email'];
            $newAccount['username'] = $data['email'];
            $newAccount['domain'] = explode('@', $data['email'])[1];
            $newAccount['profile_package_class'] = str_replace('\\', '_', Employees::class);
            $newAccount['profile_package_row_id'] = $employee['id'];

            if ($this->basepackages->accounts->add($newAccount)) {
                $employee['account_id'] = $this->basepackages->accounts->packagesData->last['id'];

                $security = [];
                $security['role_id'] = 3;//Guest;
                $security['override_role'] = false;
                $security['permissions'] = $this->helper->encode([]);
                $security['force_pwreset'] = false;
                $password = $this->basepackages->utils->generateNewPassword()['password'];
                $security['password'] = $this->secTools->hashPassword($password);
                $security['password_set_on'] = time();
                $security['account_id'] = $this->basepackages->accounts->packagesData->last['id'];

                $securityModel = new BasepackagesUsersAccountsSecurity;

                $securityStore = $this->ff->store($securityModel->getSource());

                if ($this->config->databasetype === 'db') {
                    $securityModel->assign($security);

                    $securityModel->create();
                } else {
                    $securityStore->insert($security);
                }

                $this->update($employee);
            }

            $this->updateAddresses($data, $employee);
            $this->updateContact($data, $employee);

            $this->addActivityLog($employee);

            if ($employee['portrait'] !== '') {
                $this->basepackages->storages->changeOrphanStatus(newUUID : $employee['portrait'], status: 0);
                $this->basepackages->storages->updatePackageInfo($employee['portrait'], $employee['id']);
            }

            $this->addResponse('Employee added');

            return true;
        }

        $this->addResponse('Error Adding Employee', 1);
    }

    public function updateEmployee($data)
    {
        $employee = $employeeArr = $this->getEmployees((int) $data['id']);

        if (!$employee) {
            $this->addResponse('Employee with ID not found', 1);

            return false;
        }

        $this->checkDesignation($data);

        if ($this->update(array_merge($employee, $data))) {
            $employee = $this->getEmployees($this->packagesData->last['id']);

            $this->updateAddresses($data, $employee);
            $this->updateContact($data, $employee);

            $this->addActivityLog($data, $employeeArr);

            if ($employee['portrait'] !== '') {
                $this->basepackages->storages->changeOrphanStatus(newUUID : $employee['portrait'], status: 0);
                $this->basepackages->storages->updatePackageInfo($employee['portrait'], $employee['id']);
            }

            $this->addResponse('Employee updated');

            return true;
        }

        $this->addResponse('Error Updating Employee', 1);
    }

    public function removeEmployee($data)
    {
        $employee = $this->getEmployees((int) $data['id']);

        //Archive Employee and do not delete it!
        $employee['archived'] = true;

        if ($this->updateEmployee($employee)) {
            $this->addResponse('Employee archived');

            return true;
        }

        $this->addResponse('Error removing employee', 1);

        return false;
    }

    protected function updateAddresses($data, $employee)
    {
        if (isset($data['delete_address_ids'])) {
            if (is_string($data['delete_address_ids'])) {
                $data['delete_address_ids'] = $this->helper->decode($data['delete_address_ids'], true);
            }

            if (count($data['delete_address_ids']) > 0) {
                foreach ($data['delete_address_ids'] as $addressId) {
                    $dbAddress = $this->basepackages->addressbook->getById($addressId);

                    if ($dbAddress) {
                        $this->basepackages->addressbook->removeAddress($dbAddress);
                    }
                }
            }
        }

        if (isset($data['address_ids'])) {
            if (is_string($data['address_ids'])) {
                $data['address_ids'] = $this->helper->decode($data['address_ids'], true);
            }

            if (count($data['address_ids']) > 0) {
                foreach ($data['address_ids'] as $addressId => $address) {
                    if (isset($address['new']) && $address['new'] == 1) {
                        $address['package_class'] = str_replace('\\', '_', Employees::class);
                        $address['package_row_id'] = $employee['id'];

                        $this->basepackages->addressbook->addAddress($address);
                    } else {
                        $dbAddress = $this->basepackages->addressbook->getById($addressId);

                        $dbAddress['package_class'] = str_replace('\\', '_', Employees::class);
                        $dbAddress['package_row_id'] = $employee['id'];

                        if ($dbAddress) {
                            $dbAddress = array_merge($dbAddress, $data['address_ids'][$addressId]);

                            $this->basepackages->addressbook->updateAddress($dbAddress);
                        }
                    }
                }
            }
        }

        return true;
    }

    protected function updateContact($data, $employee)
    {
        if (isset($data['id'])) {
            unset($data['id']);
        }

        if (isset($employee['contact'])) {
            $contact = $employee['contact'];
        }

        $contact['package_class'] = str_replace('\\', '_', Employees::class);
        $contact['package_row_id'] = $employee['id'];

        $contact = array_merge($contact, $data);

        if (isset($contact['first_name']) && isset($contact['last_name'])) {
            $contact['full_name'] = $contact['first_name'] . ' ' . $contact['last_name'];
        } else {
            $contact['full_name'] = $contact['first_name'];
        }

        if (isset($employee['contact']['id'])) {
            $this->basepackages->contactbook->updateContact($contact);
        } else {
            $this->basepackages->contactbook->addContact($contact);
        }

        return true;
    }

    protected function checkDesignation(&$data)
    {
        if (isset($data['designation']) && str_contains($data['designation'], '"data"')) {
            $data['designation'] = $this->helper->decode($data['designation'], true);
            if (isset($data['designation']['data'][0])) {
                $data['designation'] = $data['designation']['data'][0];
            } else if (isset($data['designation']['newTags'][0])) {
                $data['designation'] = strtolower($data['designation']['newTags'][0]);
            }
        }
    }

    public function getEmployeeAvailableStatus()
    {
        return
            [
                '1' =>
                    [
                        'id' => '1',
                        'name'  => 'Idle'
                    ],
                '2' =>
                    [
                        'id' => '2',
                        'name'  => 'On Trip'
                    ],
                '3' =>
                    [
                        'id' => '3',
                        'name'  => 'On Holiday'
                    ],
                '4' =>
                    [
                        'id' => '4',
                        'name'  => 'Terminated'
                    ]
            ];
    }
}