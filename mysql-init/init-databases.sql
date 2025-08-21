-- Create databases for Laravel application
CREATE DATABASE IF NOT EXISTS `syllaby` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `syllaby_testing` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `syllaby_pulse` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user and grant permissions
CREATE USER IF NOT EXISTS 'syllaby'@'%' IDENTIFIED BY 'password';

-- Grant permissions for main database
GRANT ALL PRIVILEGES ON `syllaby`.* TO 'syllaby'@'%';

-- Grant permissions for testing database
GRANT ALL PRIVILEGES ON `syllaby_testing`.* TO 'syllaby'@'%';

-- Grant permissions for pulse database
GRANT ALL PRIVILEGES ON `syllaby_pulse`.* TO 'syllaby'@'%';

-- Flush privileges
FLUSH PRIVILEGES;
