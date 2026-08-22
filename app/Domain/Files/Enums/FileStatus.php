<?php

namespace App\Domain\Files\Enums;

enum FileStatus: string
{
    case PendingUpload = 'pending_upload';
    case Uploaded = 'uploaded';
    case Scanning = 'scanning';
    case Ready = 'ready';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case Deleting = 'deleting';
    case Deleted = 'deleted';
}
