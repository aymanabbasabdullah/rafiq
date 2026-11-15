<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Roles available to Rafiq Admin accounts (ADM-002).
 */
enum AdminRole: string
{
    case SuperAdmin = 'super_admin';
    case ContentEditor = 'content_editor';
    case MedicalReviewer = 'medical_reviewer';
}
