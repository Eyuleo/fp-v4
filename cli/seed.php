<?php

/**
 * Database Seeder
 *
 * Populates database with initial data for development and testing
 * Usage: php cli/seed.php
 */

// Load database configuration
if (! function_exists('getDatabaseConnection')) {
    require_once __DIR__ . '/../config/database.php';
}

class DatabaseSeeder
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Run all seeders
     */
    public function run(): void
    {
        echo "Seeding database...\n\n";

        $this->seedCategories();
        $this->seedPlatformSettings();
        $this->seedTestUsers();

        echo "\nDatabase seeding completed successfully!\n";
    }

    /**
     * Seed default categories
     */
    private function seedCategories(): void
    {
        echo "Seeding categories...";

        $categories = [
            [
                'name'        => 'Web Development',
                'slug'        => 'web-development',
                'description' => 'Website design, development, and maintenance services',
            ],
            [
                'name'        => 'Graphic Design',
                'slug'        => 'graphic-design',
                'description' => 'Logo design, branding, illustrations, and visual content',
            ],
            [
                'name'        => 'Writing & Translation',
                'slug'        => 'writing-translation',
                'description' => 'Content writing, copywriting, translation, and proofreading',
            ],
            [
                'name'        => 'Digital Marketing',
                'slug'        => 'digital-marketing',
                'description' => 'Social media management, SEO, content marketing, and advertising',
            ],
            [
                'name'        => 'Video & Animation',
                'slug'        => 'video-animation',
                'description' => 'Video editing, motion graphics, and animation services',
            ],
            [
                'name'        => 'Music & Audio',
                'slug'        => 'music-audio',
                'description' => 'Audio editing, music production, and voice-over services',
            ],
            [
                'name'        => 'Programming & Tech',
                'slug'        => 'programming-tech',
                'description' => 'Software development, mobile apps, and technical support',
            ],
            [
                'name'        => 'Business',
                'slug'        => 'business',
                'description' => 'Business plans, market research, and consulting services',
            ],
            [
                'name'        => 'Data',
                'slug'        => 'data',
                'description' => 'Data analysis, data entry, and database management',
            ],
            [
                'name'        => 'Photography',
                'slug'        => 'photography',
                'description' => 'Photo editing, retouching, and photography services',
            ],
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE description = VALUES(description)"
        );

        foreach ($categories as $category) {
            $stmt->execute([
                $category['name'],
                $category['slug'],
                $category['description'],
            ]);
        }

        echo " DONE (" . count($categories) . " categories)\n";
    }

    /**
     * Seed platform settings
     */
    private function seedPlatformSettings(): void
    {
        echo "Seeding platform settings...";

        $settings = [
            ['commission_rate', '15'],
            ['max_revisions', '3'],
            ['min_order_amount', '5'],
            ['max_order_amount', '10000'],
            ['platform_name', 'Student Skills Marketplace'],
            ['support_email', 'support@marketplace.local'],
            ['currency', 'USD'],
            ['timezone', 'Africa/Addis_Ababa'],
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO platform_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );

        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }

        echo " DONE (" . count($settings) . " settings)\n";
    }

    /**
     * Seed test users for development
     */
    private function seedTestUsers(): void
    {
        echo "Seeding test users...";

        // Check if users already exist
        $stmt      = $this->pdo->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();

        if ($userCount > 0) {
            echo " SKIPPED (users already exist)\n";
            return;
        }

        // Default password: "password123" (hashed with bcrypt cost 12)
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);

        $users = [
            [
                'email'             => 'admin@marketplace.local',
                'password_hash'     => $passwordHash,
                'role'              => 'admin',
                'status'            => 'active',
                'email_verified_at' => date('Y-m-d H:i:s'),
            ],
            [
                'email'             => 'student@marketplace.local',
                'password_hash'     => $passwordHash,
                'role'              => 'student',
                'status'            => 'active',
                'email_verified_at' => date('Y-m-d H:i:s'),
            ],
            [
                'email'             => 'client@marketplace.local',
                'password_hash'     => $passwordHash,
                'role'              => 'client',
                'status'            => 'active',
                'email_verified_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO users (email, password_hash, role, status, email_verified_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        foreach ($users as $user) {
            $stmt->execute([
                $user['email'],
                $user['password_hash'],
                $user['role'],
                $user['status'],
                $user['email_verified_at'],
            ]);

            // Create student profile for student user
            if ($user['role'] === 'student') {
                $studentId   = $this->pdo->lastInsertId();
                $profileStmt = $this->pdo->prepare(
                    "INSERT INTO student_profiles (user_id, bio, skills) VALUES (?, ?, ?)"
                );
                $profileStmt->execute([
                    $studentId,
                    'Sample student profile for testing purposes.',
                    json_encode(['PHP', 'JavaScript', 'MySQL', 'HTML', 'CSS']),
                ]);
            }
        }

        echo " DONE (" . count($users) . " users)\n";
        echo "\nTest user credentials:\n";
        echo "  Admin:   admin@marketplace.local / password123\n";
        echo "  Student: student@marketplace.local / password123\n";
        echo "  Client:  client@marketplace.local / password123\n";
    }
}

// Main execution
try {
    $pdo    = getDatabaseConnection();
    $seeder = new DatabaseSeeder($pdo);
    $seeder->run();
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    exit(1);
}
