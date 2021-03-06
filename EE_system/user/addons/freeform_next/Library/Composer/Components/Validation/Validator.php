<?php

namespace Solspace\Addons\FreeformNext\Library\Composer\Components\Validation;

use Solspace\Addons\FreeformNext\Library\Composer\Components\AbstractField;
use Solspace\Addons\FreeformNext\Library\Composer\Components\Validation\Errors\ConstraintViolationList;

class Validator
{
    /**
     * @param AbstractField $field
     * @param mixed         $value
     *
     * @return ConstraintViolationList
     */
    public function validate(AbstractField $field, $value)
    {
        $violationList = new ConstraintViolationList();

        $constraints = $field->getConstraints();
        foreach ($constraints as $constraint) {
            $violationList->merge($constraint->validate($value));
        }

        return $violationList;
    }
}
