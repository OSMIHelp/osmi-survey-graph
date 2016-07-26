<?php

namespace OSMI\Survey\Graph\Enum;

use MyCLabs\Enum\Enum;

class Diagnosis extends Enum
{
    const SELF_DIAGNOSIS = 'self';
    const PROFESSIONAL_DIAGNOSIS = 'professional';
    const CURRENT_DIAGNOSIS = 'current';
}
