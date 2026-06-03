<?php

namespace Apps\Tms\Packages\Employees;

use Apps\Tms\Packages\Employees\Model\AppsTmsEmployees;
use System\Base\BasePackage;

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

    public function getEmployee($employeeId)
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
            $this->setFFRelationsConditions(['addresses' => ['package_name', '=', 'Employees'], 'contacts' => ['package_name', '=', 'Employees']]);

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
        if ($this->add($data)) {
            $employee = $this->packagesData->last;

            $this->addAddresses($data, $employee);

            $this->addResponse('Employee added');

            return true;
        }

        $this->addResponse('Error Adding Employee', 1);
    }

    public function updateEmployee($data)
    {
        if ($this->update($data)) {
            $employee = $this->packagesData->last;

            $this->addAddresses($data, $employee);
            $this->removeAddresses($data, $employee);

            $this->addResponse('Employee updated');

            return true;
        }

        $this->addResponse('Error Updating Employee', 1);
    }

    public function removeEmployee($data)
    {
        $employee = $this->getEmployee($data['id']);

        //Archive Employee and do not delete it!
        $employee['archived'] = true;

        if ($this->updateEmployee($employee)) {
            $this->addResponse('Employee archived');

            return true;
        }

        $this->addResponse('Error removing employee', 1);

        return false;
    }

    protected function addAddresses($data, $employee)
    {
        if (isset($data['address_ids'])) {
            if (is_string($data['address_ids'])) {
                $data['address_ids'] = $this->helper->decode($data['address_ids'], true);
            }

            if (count($data['address_ids']) > 0) {
                foreach ($data['address_ids'] as $addressId => $address) {
                    if (isset($address['new']) && $address['new'] == 1) {
                        $address['package_name'] = 'Employees';
                        $address['package_row_id'] = $employee['id'];

                        $this->basepackages->addressbook->addAddress($address);
                    } else {
                        $dbAddress = $this->basepackages->addressbook->getById($addressId);

                        if ($dbAddress) {
                            $dbAddress = array_merge($dbAddress, $data['address_ids'][$addressId]);
                        }

                        $this->basepackages->addressbook->updateAddress($dbAddress);
                    }
                }
            }
        }
    }

    protected function removeAddresses($data, $employee)
    {
        if (isset($data['delete_address_ids'])) {
            if (is_string($data['delete_address_ids'])) {
                $data['delete_address_ids'] = $this->helper->decode($data['delete_address_ids'], true);
            }

            if (count($data['delete_address_ids']) > 0) {
                foreach ($data['delete_address_ids'] as $addressId) {
                    $dbAddress = $this->basepackages->addressbook->getById($addressId);

                    //Check if address is being used by invoice and other locations!!!!
                    //
                    if ($dbAddress) {
                        $this->basepackages->addressbook->removeAddress($dbAddress);
                    }
                }
            }
        }
    }

    public function getEmployeeByReference($reference, $businessType = 'customers')
    {
        if ($this->config->databasetype === 'db') {
            $params =
                [
                    'conditions'    => 'reference = :reference: AND business_type = :businessType:',
                    'bind'          =>
                        [
                            'reference'         => $reference,
                            'businessType'      => $businessType,
                        ]
                ];
        } else {
            $params = ['conditions' => [['reference', '=', $reference], ['business_type', '=', $businessType]]];
        }

        $employee = $this->getByParams($params);

        if ($employee && count($employee) > 0) {
            $employee = $this->getEmployee($employee[0]['id']);

            return $employee;
        }

        return false;
    }

    public function getEmployeesByBusinessType($businessType = 'organisations')
    {
        if ($this->config->databasetype === 'db') {
            $params =
                [
                    'conditions'    => 'business_type = :businessType:',
                    'bind'          =>
                        [
                            'businessType'      => $businessType,
                        ]
                ];
        } else {
            $params = ['conditions' => ['business_type', '=', $businessType]];
        }

        $employees = $this->getByParams($params);

        if ($employees && count($employees) > 0) {
            return $employees;
        }

        return false;
    }
}