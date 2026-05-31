<?php

namespace Apps\Tms\Packages\Employees;

use System\Base\BasePackage;

class Employees extends BasePackage
{
    //protected $modelToUse = ::class;

    protected $packageName = 'employees';

    public $employees;

    public function init()
    {
        //Note: If you want to use init function, you need to run parent::init as well.
        //It is used by the use app database feature of the app.
        //if you remove the init() function from this class, it is also fine.
        parent::init();

        return $this;
    }

    public function getEmployeesById($id)
    {
        $employees = $this->getById($id);

        if ($employees) {
            //
            $this->addResponse('Success');

            return;
        }

        $this->addResponse('Error', 1);
    }

    public function addEmployees($data)
    {
        //
    }

    public function updateEmployees($data)
    {
        $employees = $this->getById($id);

        if ($employees) {
            //
            $this->addResponse('Success');

            return;
        }

        $this->addResponse('Error', 1);
    }

    public function removeEmployees($data)
    {
        $employees = $this->getById($id);

        if ($employees) {
            //
            $this->addResponse('Success');

            return;
        }

        $this->addResponse('Error', 1);
    }
}