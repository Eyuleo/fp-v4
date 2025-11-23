<?php

/**
 * Service Validator
 *
 * Validates service input data
 */
class ServiceValidator
{
    private array $errors = [];

    /**
     * Validate service creation data
     *
     * @param array $data
     * @return bool
     */
    public function validateCreate(array $data): bool
    {
        $this->errors = [];

        // Validate title
        if (empty($data['title'])) {
            $this->errors['title'] = 'Title is required';
        } elseif (strlen($data['title']) < 5) {
            $this->errors['title'] = 'Title must be at least 5 characters';
        } elseif (strlen($data['title']) > 255) {
            $this->errors['title'] = 'Title must not exceed 255 characters';
        }

        // Validate description
        if (empty($data['description'])) {
            $this->errors['description'] = 'Description is required';
        } elseif (strlen($data['description']) < 20) {
            $this->errors['description'] = 'Description must be at least 20 characters';
        }

        // Validate category
        if (empty($data['category_id'])) {
            $this->errors['category_id'] = 'Category is required';
        } elseif (! is_numeric($data['category_id']) || $data['category_id'] <= 0) {
            $this->errors['category_id'] = 'Invalid category selected';
        }

        // Validate price
        if (! isset($data['price']) || $data['price'] === '') {
            $this->errors['price'] = 'Price is required';
        } elseif (! is_numeric($data['price']) || $data['price'] <= 0) {
            $this->errors['price'] = 'Price must be greater than 0';
        } elseif ($data['price'] > 999999.99) {
            $this->errors['price'] = 'Price must not exceed $999,999.99';
        }

        // Validate delivery days
        if (! isset($data['delivery_days']) || $data['delivery_days'] === '') {
            $this->errors['delivery_days'] = 'Delivery days is required';
        } elseif (! is_numeric($data['delivery_days']) || $data['delivery_days'] <= 0) {
            $this->errors['delivery_days'] = 'Delivery days must be greater than 0';
        } elseif ($data['delivery_days'] > 365) {
            $this->errors['delivery_days'] = 'Delivery days must not exceed 365';
        }

        // Validate tags (optional)
        if (isset($data['tags']) && ! empty($data['tags'])) {
            if (is_string($data['tags'])) {
                // Convert comma-separated string to array
                $data['tags'] = array_map('trim', explode(',', $data['tags']));
            }

            if (! is_array($data['tags'])) {
                $this->errors['tags'] = 'Tags must be an array or comma-separated string';
            } elseif (count($data['tags']) > 10) {
                $this->errors['tags'] = 'Maximum 10 tags allowed';
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate service update data
     *
     * @param array $data
     * @param bool $skipRestrictedFields Skip validation for fields that may be locked
     * @return bool
     */
    public function validateUpdate(array $data, bool $skipRestrictedFields = false): bool
    {
        $this->errors = [];

        // Validate title (always required)
        if (empty($data['title'])) {
            $this->errors['title'] = 'Title is required';
        } elseif (strlen($data['title']) < 5) {
            $this->errors['title'] = 'Title must be at least 5 characters';
        } elseif (strlen($data['title']) > 255) {
            $this->errors['title'] = 'Title must not exceed 255 characters';
        }

        // Only validate restricted fields if they're present in the data
        // (they won't be present if service has active orders)
        
        // Validate description (only if present)
        if (isset($data['description'])) {
            if (empty($data['description'])) {
                $this->errors['description'] = 'Description is required';
            } elseif (strlen($data['description']) < 20) {
                $this->errors['description'] = 'Description must be at least 20 characters';
            }
        }

        // Validate category (only if present)
        if (isset($data['category_id'])) {
            if (empty($data['category_id'])) {
                $this->errors['category_id'] = 'Category is required';
            } elseif (! is_numeric($data['category_id']) || $data['category_id'] <= 0) {
                $this->errors['category_id'] = 'Invalid category selected';
            }
        }

        // Validate price (only if present)
        if (isset($data['price'])) {
            if ($data['price'] === '') {
                $this->errors['price'] = 'Price is required';
            } elseif (! is_numeric($data['price']) || $data['price'] <= 0) {
                $this->errors['price'] = 'Price must be greater than 0';
            } elseif ($data['price'] > 999999.99) {
                $this->errors['price'] = 'Price must not exceed $999,999.99';
            }
        }

        // Validate delivery days (only if present)
        if (isset($data['delivery_days'])) {
            if ($data['delivery_days'] === '') {
                $this->errors['delivery_days'] = 'Delivery days is required';
            } elseif (! is_numeric($data['delivery_days']) || $data['delivery_days'] <= 0) {
                $this->errors['delivery_days'] = 'Delivery days must be greater than 0';
            } elseif ($data['delivery_days'] > 365) {
                $this->errors['delivery_days'] = 'Delivery days must not exceed 365';
            }
        }

        // Validate tags (optional)
        if (isset($data['tags']) && ! empty($data['tags'])) {
            if (is_string($data['tags'])) {
                // Convert comma-separated string to array
                $data['tags'] = array_map('trim', explode(',', $data['tags']));
            }

            if (! is_array($data['tags'])) {
                $this->errors['tags'] = 'Tags must be an array or comma-separated string';
            } elseif (count($data['tags']) > 10) {
                $this->errors['tags'] = 'Maximum 10 tags allowed';
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate file uploads
     *
     * @param array $files
     * @return bool
     */
    public function validateFiles(array $files): bool
    {
        if (empty($files)) {
            return true; // Files are optional
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $maxFileSize       = 10 * 1024 * 1024; // 10MB
        $maxFiles          = 5;

        if (count($files) > $maxFiles) {
            $this->errors['sample_files'] = "Maximum $maxFiles files allowed";
            return false;
        }

        foreach ($files as $index => $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->errors['sample_files'] = 'File upload error occurred';
                return false;
            }

            // Check file size
            if ($file['size'] > $maxFileSize) {
                $this->errors['sample_files'] = 'Each file must not exceed 10MB';
                return false;
            }

            // Check file extension
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (! in_array($extension, $allowedExtensions)) {
                $this->errors['sample_files'] = 'Invalid file type. Allowed: JPG, JPEG, PNG, GIF, PDF only';
                return false;
            }

            // Check MIME type
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimeTypes = [
                'image/jpeg', 'image/png', 'image/gif',
                'application/pdf',
            ];

            if (! in_array($mimeType, $allowedMimeTypes)) {
                $this->errors['sample_files'] = 'Invalid file type detected. Only images (JPG, PNG, GIF) and PDF files are allowed';
                return false;
            }
        }

        return true;
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     *
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        return reset($this->errors);
    }
}
