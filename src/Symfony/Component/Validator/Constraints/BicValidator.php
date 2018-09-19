<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Constraints;

use Symfony\Component\Intl\Intl;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @author Michael Hirschler <michael.vhirsch@gmail.com>
 *
 * @see https://en.wikipedia.org/wiki/ISO_9362#Structure
 */
class BicValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $canonicalize = str_replace(' ', '', $value);

        // the bic must be either 8 or 11 characters long
        if (!\in_array(\strlen($canonicalize), array(8, 11))) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Bic::INVALID_LENGTH_ERROR)
                ->addViolation();

            return;
        }

        // must contain alphanumeric values only
        if (!ctype_alnum($canonicalize)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Bic::INVALID_CHARACTERS_ERROR)
                ->addViolation();

            return;
        }

        // first 4 letters must be alphabetic (bank code)
        if (!ctype_alpha(substr($canonicalize, 0, 4))) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Bic::INVALID_BANK_CODE_ERROR)
                ->addViolation();

            return;
        }

        if (!class_exists(Intl::class)) {
            throw new LogicException('The "symfony/intl" component is required to use the Bic constraint.');
        }

        // next 2 letters must be alphabetic (country code)
        $countries = Intl::getRegionBundle()->getCountryNames();
        if (!isset($countries[substr($canonicalize, 4, 2)])) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Bic::INVALID_COUNTRY_CODE_ERROR)
                ->addViolation();

            return;
        }

        // should contain uppercase characters only
        if (strtoupper($canonicalize) !== $canonicalize) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Bic::INVALID_CASE_ERROR)
                ->addViolation();

            return;
        }
    }
}
