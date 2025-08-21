/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `account_publications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_publications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `publication_id` bigint unsigned NOT NULL,
  `social_channel_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `provider_media_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'The id of the media in the provider''s platform',
  `error_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_publications_social_channel_id_foreign` (`social_channel_id`),
  KEY `pub_id_sc_id_status_index` (`publication_id`,`social_channel_id`,`status`),
  CONSTRAINT `account_publications_publication_id_foreign` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `account_publications_social_channel_id_foreign` FOREIGN KEY (`social_channel_id`) REFERENCES `social_channels` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `answers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `question_id` bigint unsigned NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `answers_user_id_index` (`user_id`),
  KEY `answers_question_id_index` (`question_id`),
  CONSTRAINT `answers_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `answers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `parent_id` bigint DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `style` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_private` tinyint(1) NOT NULL DEFAULT '1',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orientation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `retries` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assets_provider_id_unique` (`provider_id`),
  KEY `assets_user_id_foreign` (`user_id`),
  KEY `assets_type_index` (`type`),
  KEY `assets_status_index` (`status`),
  FULLTEXT KEY `assets_description_fulltext` (`description`),
  CONSTRAINT `assets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `avatars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `avatars` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `race` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preview_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `avatars_user_id_foreign` (`user_id`),
  CONSTRAINT `avatars_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookmarks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookmarks_user_id_model_type_model_id_unique` (`user_id`,`model_type`,`model_id`),
  KEY `bookmarks_model_type_model_id_index` (`model_type`,`model_id`),
  CONSTRAINT `bookmarks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `captions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `captions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `provider` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` json DEFAULT NULL,
  `hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `captions_user_id_foreign` (`user_id`),
  KEY `captions_model_type_model_id_index` (`model_type`,`model_id`),
  CONSTRAINT `captions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `card_fingerprints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card_fingerprints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `fingerprint` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `card_fingerprints_user_id_fingerprint_unique` (`user_id`,`fingerprint`),
  KEY `card_fingerprints_user_id_fingerprint_updated_at_index` (`user_id`,`fingerprint`,`updated_at`),
  CONSTRAINT `card_fingerprints_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clonables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clonables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `purchase_id` bigint unsigned DEFAULT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clonables_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `clonables_user_id_index` (`user_id`),
  KEY `clonables_purchase_id_index` (`purchase_id`),
  CONSTRAINT `clonables_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `clonables_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `content_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_articles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `outline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `style` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_in_months` int unsigned DEFAULT NULL,
  `max_redemptions` int unsigned DEFAULT NULL,
  `amount_off` double DEFAULT NULL,
  `percent_off` double DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `type` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`),
  KEY `coupons_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` tinyint NOT NULL,
  `calculation_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` double NOT NULL,
  `min_amount` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `credit_events_id` bigint unsigned NOT NULL,
  `creditable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creditable_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calculative_index` int unsigned NOT NULL,
  `event_value` int unsigned NOT NULL,
  `amount` int unsigned NOT NULL,
  `previous_amount` int unsigned NOT NULL,
  `event_type` tinyint NOT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_histories_creditable_type_creditable_id_index` (`creditable_type`,`creditable_id`),
  KEY `credit_histories_credit_events_id_foreign` (`credit_events_id`),
  KEY `credit_histories_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `credit_histories_credit_events_id_foreign` FOREIGN KEY (`credit_events_id`) REFERENCES `credit_events` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `credit_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `editor_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `editor_assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preview` json NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `editor_assets_user_id_foreign` (`user_id`),
  CONSTRAINT `editor_assets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `scheduler_id` bigint unsigned DEFAULT NULL,
  `color` varchar(48) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` bigint unsigned DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `events_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `events_user_id_index` (`user_id`),
  KEY `events_campaign_id_index` (`scheduler_id`),
  KEY `events_completed_at_model_type_starts_at_index` (`completed_at`,`model_type`,`starts_at`),
  KEY `events_completed_at_model_type_index` (`completed_at`,`model_type`),
  CONSTRAINT `events_scheduler_id_foreign` FOREIGN KEY (`scheduler_id`) REFERENCES `schedulers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `faceless_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faceless_presets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `voice_id` bigint unsigned DEFAULT NULL,
  `music_id` bigint unsigned DEFAULT NULL,
  `resource_id` bigint unsigned DEFAULT NULL,
  `genre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `background_id` bigint unsigned DEFAULT NULL,
  `watermark_id` bigint unsigned DEFAULT NULL,
  `watermark_position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `watermark_opacity` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `font_family` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `font_color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caption_animation` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_animation` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orientation` varchar(26) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transition` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `volume` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sfx` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overlay` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `faceless_presets_user_id_foreign` (`user_id`),
  KEY `faceless_presets_voice_id_foreign` (`voice_id`),
  KEY `faceless_presets_music_id_foreign` (`music_id`),
  KEY `faceless_presets_background_id_foreign` (`background_id`),
  KEY `faceless_presets_watermark_id_foreign` (`watermark_id`),
  KEY `faceless_presets_resource_id_foreign` (`resource_id`),
  CONSTRAINT `faceless_presets_background_id_foreign` FOREIGN KEY (`background_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `faceless_presets_music_id_foreign` FOREIGN KEY (`music_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `faceless_presets_resource_id_foreign` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `faceless_presets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `faceless_presets_voice_id_foreign` FOREIGN KEY (`voice_id`) REFERENCES `voices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `faceless_presets_watermark_id_foreign` FOREIGN KEY (`watermark_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facelesses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facelesses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_id` bigint unsigned NOT NULL,
  `voice_id` bigint unsigned DEFAULT NULL,
  `background_id` bigint unsigned DEFAULT NULL,
  `music_id` bigint unsigned DEFAULT NULL,
  `watermark_id` bigint unsigned DEFAULT NULL,
  `estimated_duration` int DEFAULT NULL COMMENT 'The estimated duration of the faceless video in seconds.',
  `is_transcribed` tinyint(1) NOT NULL DEFAULT '0',
  `genre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `script` text COLLATE utf8mb4_unicode_ci,
  `hash` json DEFAULT NULL,
  `options` json DEFAULT NULL COMMENT 'Options for the faceless video. Would be used to populate edit page',
  `batch` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facelesses_user_id_index` (`user_id`),
  KEY `facelesses_video_id_index` (`video_id`),
  KEY `facelesses_voice_id_index` (`voice_id`),
  KEY `facelesses_background_id_index` (`background_id`),
  KEY `facelesses_music_id_foreign` (`music_id`),
  KEY `facelesses_watermark_id_foreign` (`watermark_id`),
  KEY `facelesses_batch_index` (`batch`),
  CONSTRAINT `facelesses_background_id_foreign` FOREIGN KEY (`background_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `facelesses_music_id_foreign` FOREIGN KEY (`music_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `facelesses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `facelesses_video_id_foreign` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `facelesses_voice_id_foreign` FOREIGN KEY (`voice_id`) REFERENCES `voices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `facelesses_watermark_id_foreign` FOREIGN KEY (`watermark_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `features` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `features_name_scope_unique` (`name`,`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `folders_user_id_foreign` (`user_id`),
  CONSTRAINT `folders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `footages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `footages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `video_id` bigint unsigned NOT NULL,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preference` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `footages_user_id_index` (`user_id`),
  KEY `footages_video_id_index` (`video_id`),
  CONSTRAINT `footages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `footages_video_id_foreign` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `generators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `generators` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `topic` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `length` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `style` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `context` json DEFAULT NULL,
  `output` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `generators_model_type_model_id_index` (`model_type`,`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ideas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ideas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `keyword_id` bigint unsigned NOT NULL,
  `title` text COLLATE utf8mb4_unicode_ci,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trend` double DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `volume` bigint unsigned DEFAULT NULL,
  `cpc` double DEFAULT NULL,
  `competition` double DEFAULT NULL,
  `competition_label` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_results` bigint unsigned DEFAULT NULL,
  `trends` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valid_until` date NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ideas_slug_unique` (`slug`),
  KEY `ideas_keyword_id_index` (`keyword_id`),
  KEY `ideas_type_index` (`type`),
  CONSTRAINT `ideas_keyword_id_foreign` FOREIGN KEY (`keyword_id`) REFERENCES `keywords` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `industries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `industries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `industries_name_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `industry_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `industry_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `industry_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `industry_user_user_id_index` (`user_id`),
  KEY `industry_user_industry_id_index` (`industry_id`),
  CONSTRAINT `industry_user_industry_id_foreign` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `industry_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `keyword_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `keyword_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `keyword_id` bigint unsigned NOT NULL,
  `audience` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `keyword_user_user_id_foreign` (`user_id`),
  KEY `keyword_user_keyword_id_foreign` (`keyword_id`),
  CONSTRAINT `keyword_user_keyword_id_foreign` FOREIGN KEY (`keyword_id`) REFERENCES `keywords` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `keyword_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `keywords` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `network` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `keywords_slug_network_unique` (`slug`,`network`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`),
  KEY `user_storage_index` (`user_id`,`model_type`,`size`),
  CONSTRAINT `media_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `metadata` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `values` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` json NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  KEY `notifications_notifiable_type_notifiable_id_created_at_index` (`notifiable_type`,`notifiable_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `open_ai_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `open_ai_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_prompt` mediumtext COLLATE utf8mb4_unicode_ci,
  `request_model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_max_tokens` int DEFAULT NULL,
  `request_temperature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_top_p` int DEFAULT NULL,
  `request_frequency_penalty` int DEFAULT NULL,
  `request_presence_penalty` int DEFAULT NULL,
  `response_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response_text` longtext COLLATE utf8mb4_unicode_ci,
  `response_finish_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response_prompt_tokens` decimal(8,2) DEFAULT NULL,
  `response_completion_tokens` decimal(8,2) DEFAULT NULL,
  `response_total_tokens` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `plan_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` double NOT NULL DEFAULT '0',
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plans_plan_id_unique` (`plan_id`),
  KEY `plans_parent_id_foreign` (`parent_id`),
  CONSTRAINT `plans_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `plans` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `publication_aggregates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `publication_aggregates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `publication_id` bigint unsigned NOT NULL,
  `social_channel_id` bigint unsigned NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of the metric .e.g views, likes',
  `value` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type of the aggregate e.g sum, average',
  `last_updated_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `publication_aggregates_unique` (`publication_id`,`social_channel_id`,`key`),
  KEY `publication_aggregates_social_channel_id_foreign` (`social_channel_id`),
  CONSTRAINT `publication_aggregates_publication_id_foreign` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `publication_aggregates_social_channel_id_foreign` FOREIGN KEY (`social_channel_id`) REFERENCES `social_channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `publication_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `publication_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `publication_id` bigint unsigned DEFAULT NULL,
  `social_channel_id` bigint unsigned DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `publication_logs_publication_id_social_channel_id_index` (`publication_id`,`social_channel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `publication_metric_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `publication_metric_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `publication_metric_keys_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `publication_metric_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `publication_metric_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `publication_id` bigint unsigned NOT NULL,
  `social_channel_id` bigint unsigned NOT NULL,
  `publication_metric_key_id` bigint unsigned NOT NULL,
  `value` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date GENERATED ALWAYS AS (cast(`created_at` as date)) VIRTUAL /*!80023 INVISIBLE */,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `publication_metric_values_unique` (`publication_id`,`social_channel_id`,`publication_metric_key_id`,`date`),
  KEY `publication_metric_values_social_channel_id_foreign` (`social_channel_id`),
  KEY `publication_metric_values_publication_metric_key_id_foreign` (`publication_metric_key_id`),
  KEY `idx_pub_metrics_latest` (`publication_id`,`social_channel_id`,`publication_metric_key_id`,`created_at`),
  CONSTRAINT `publication_metric_values_publication_id_foreign` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `publication_metric_values_publication_metric_key_id_foreign` FOREIGN KEY (`publication_metric_key_id`) REFERENCES `publication_metric_keys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `publication_metric_values_social_channel_id_foreign` FOREIGN KEY (`social_channel_id`) REFERENCES `social_channels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `publications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `publications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `video_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `draft` tinyint(1) NOT NULL DEFAULT '1',
  `temporary` tinyint(1) NOT NULL DEFAULT '1',
  `scheduled` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `publications_video_id_foreign` (`video_id`),
  KEY `publications_user_id_temporary_created_at_index` (`user_id`,`temporary`,`created_at`),
  CONSTRAINT `publications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `publications_video_id_foreign` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned NOT NULL,
  `payment_intent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchases_user_id_index` (`user_id`),
  KEY `purchases_plan_id_index` (`plan_id`),
  CONSTRAINT `purchases_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `purchases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `survey_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `placeholder` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `selected` text COLLATE utf8mb4_unicode_ci,
  `options` json DEFAULT NULL,
  `rules` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `questions_slug_unique` (`slug`),
  KEY `questions_survey_id_foreign` (`survey_id`),
  CONSTRAINT `questions_survey_id_foreign` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `real_clones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `real_clones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `footage_id` bigint unsigned DEFAULT NULL,
  `voice_id` bigint unsigned DEFAULT NULL,
  `avatar_id` bigint unsigned DEFAULT NULL,
  `background` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` text COLLATE utf8mb4_unicode_ci,
  `script` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hash` json DEFAULT NULL,
  `retries` int unsigned NOT NULL DEFAULT '0',
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `real_clones_provider_provider_id_index` (`provider`,`provider_id`),
  KEY `real_clones_user_id_index` (`user_id`),
  KEY `real_clones_footage_id_index` (`footage_id`),
  KEY `real_clones_voice_id_index` (`voice_id`),
  KEY `real_clones_avatar_id_index` (`avatar_id`),
  CONSTRAINT `real_clones_avatar_id_foreign` FOREIGN KEY (`avatar_id`) REFERENCES `avatars` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `real_clones_footage_id_foreign` FOREIGN KEY (`footage_id`) REFERENCES `footages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `real_clones_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `real_clones_voice_id_foreign` FOREIGN KEY (`voice_id`) REFERENCES `voices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redemptions` (
  `user_id` bigint unsigned NOT NULL,
  `coupon_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`coupon_id`,`user_id`),
  KEY `redemptions_user_id_foreign` (`user_id`),
  CONSTRAINT `redemptions_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `related_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `related_topics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hash` varchar(32) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (md5(`title`)) STORED,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ideas` json DEFAULT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `related_topics_user_id_hash_language_unique` (`user_id`,`hash`,`language`),
  CONSTRAINT `related_topics_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resources_user_id_foreign` (`user_id`),
  KEY `resources_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `resources_parent_id_foreign` (`parent_id`),
  CONSTRAINT `resources_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `resources` (`id`),
  CONSTRAINT `resources_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scheduler_occurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheduler_occurrences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `scheduler_id` bigint unsigned NOT NULL,
  `topic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `script` text COLLATE utf8mb4_unicode_ci,
  `occurs_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scheduler_occurrences_scheduler_id_occurs_at_unique` (`scheduler_id`,`occurs_at`),
  KEY `scheduler_occurrences_user_id_foreign` (`user_id`),
  CONSTRAINT `scheduler_occurrences_scheduler_id_foreign` FOREIGN KEY (`scheduler_id`) REFERENCES `schedulers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `scheduler_occurrences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scheduler_social_channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheduler_social_channel` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scheduler_id` bigint unsigned NOT NULL,
  `social_channel_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scheduler_social_channel_scheduler_id_foreign` (`scheduler_id`),
  KEY `scheduler_social_channel_social_channel_id_foreign` (`social_channel_id`),
  CONSTRAINT `scheduler_social_channel_scheduler_id_foreign` FOREIGN KEY (`scheduler_id`) REFERENCES `schedulers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `scheduler_social_channel_social_channel_id_foreign` FOREIGN KEY (`social_channel_id`) REFERENCES `social_channels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedulers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedulers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `idea_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(48) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `topic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` json DEFAULT NULL,
  `rrules` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaigns_user_id_index` (`user_id`),
  KEY `campaigns_idea_id_index` (`idea_id`),
  CONSTRAINT `campaigns_idea_id_foreign` FOREIGN KEY (`idea_id`) REFERENCES `ideas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `campaigns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scraper_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scraper_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response` json DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `format` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `provider` tinyint NOT NULL,
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access_token` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_in` int DEFAULT NULL,
  `refresh_token` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `refresh_expires_in` int DEFAULT NULL,
  `needs_reauth` tinyint(1) NOT NULL DEFAULT '0',
  `errors` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `social_accounts_user_id_foreign` (`user_id`),
  CONSTRAINT `social_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_channels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `social_account_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access_token` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Channels specific access token (if any e.g Meta)',
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `social_channels_provider_id_unique` (`provider_id`),
  KEY `social_channels_social_account_id_foreign` (`social_account_id`),
  CONSTRAINT `social_channels_social_account_id_foreign` FOREIGN KEY (`social_account_id`) REFERENCES `social_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `speeches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `speeches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `real_clone_id` bigint unsigned DEFAULT NULL,
  `voice_id` bigint unsigned DEFAULT NULL,
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `speeches_real_clone_id_index` (`real_clone_id`),
  KEY `speeches_user_id_foreign` (`user_id`),
  KEY `speeches_voice_id_foreign` (`voice_id`),
  CONSTRAINT `speeches_real_clone_id_foreign` FOREIGN KEY (`real_clone_id`) REFERENCES `real_clones` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `speeches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `speeches_voice_id_foreign` FOREIGN KEY (`voice_id`) REFERENCES `voices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stripe_event_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stripe_event_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint unsigned NOT NULL,
  `stripe_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_product` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_price` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_items_subscription_id_stripe_price_unique` (`subscription_id`,`stripe_price`),
  UNIQUE KEY `subscription_items_stripe_id_unique` (`stripe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduler_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_price` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_stripe_id_unique` (`stripe_id`),
  KEY `subscriptions_user_id_stripe_status_index` (`user_id`,`stripe_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppressions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppressions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trace_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `soft_bounce_count` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `bounce_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bounced_at` datetime DEFAULT NULL,
  `complained_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppressions_email_unique` (`email`),
  KEY `suppressions_email_bounced_at_complained_at_index` (`email`,`bounced_at`,`complained_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `surveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `surveys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `surveys_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `taggables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `taggables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` bigint unsigned NOT NULL,
  `taggable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taggable_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `taggables_taggable_type_taggable_id_index` (`taggable_type`,`taggable_id`),
  KEY `taggables_tag_id_foreign` (`tag_id`),
  CONSTRAINT `taggables_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_slug_unique` (`slug`),
  KEY `tags_user_id_foreign` (`user_id`),
  CONSTRAINT `tags_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(48) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `source` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `templates_slug_unique` (`slug`),
  KEY `templates_slug_type_index` (`slug`,`type`),
  KEY `templates_type_index` (`type`),
  KEY `templates_user_id_foreign` (`user_id`),
  CONSTRAINT `templates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timelines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timelines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `provider` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` json DEFAULT NULL,
  `hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timelines_user_id_foreign` (`user_id`),
  KEY `timelines_model_type_model_id_index` (`model_type`,`model_id`),
  CONSTRAINT `timelines_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trackers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trackers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `trackable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trackable_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `count` int NOT NULL DEFAULT '0',
  `limit` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trackers_trackable_type_trackable_id_index` (`trackable_type`,`trackable_id`),
  KEY `trackers_user_id_trackable_type_trackable_id_name_index` (`user_id`,`trackable_type`,`trackable_id`,`name`),
  CONSTRAINT `trackers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_feedback` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_feedback_user_id_foreign` (`user_id`),
  CONSTRAINT `user_feedback_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_view`;
/*!50001 DROP VIEW IF EXISTS `user_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `user_view` AS SELECT 
 1 AS `id`,
 1 AS `settings`,
 1 AS `notifications`,
 1 AS `mailing_list`,
 1 AS `email_verified_at`,
 1 AS `user_type`,
 1 AS `remember_token`,
 1 AS `plan_id`,
 1 AS `subscription_ends_at`,
 1 AS `trial_ends_at`,
 1 AS `pm_last_four`,
 1 AS `pm_type`,
 1 AS `stripe_id`,
 1 AS `created_at`,
 1 AS `updated_at`,
 1 AS `remaining_credit_amount`,
 1 AS `monthly_credit_amount`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` tinyint DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `registration_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `notifications` json DEFAULT NULL,
  `mailing_list` tinyint(1) NOT NULL DEFAULT '1',
  `email_verified_at` datetime DEFAULT NULL,
  `user_type` tinyint NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plan_id` int unsigned DEFAULT NULL,
  `subscription_ends_at` timestamp NULL DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `pm_last_four` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pm_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remaining_credit_amount` bigint unsigned DEFAULT NULL,
  `monthly_credit_amount` int DEFAULT NULL,
  `extra_credits` bigint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_provider_provider_id_unique` (`provider`,`provider_id`),
  KEY `users_stripe_id_index` (`stripe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `video_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `video_assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `asset_id` bigint unsigned NOT NULL,
  `order` int NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `video_assets_uuid_unique` (`uuid`),
  KEY `video_assets_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `video_assets_asset_id_foreign` (`asset_id`),
  KEY `video_assets_model_type_model_id_active_index` (`model_type`,`model_id`,`active`),
  CONSTRAINT `video_assets_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `video_generator_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `video_generator_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `video_id` bigint unsigned NOT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `render_started_at` timestamp NULL DEFAULT NULL,
  `render_finished_at` timestamp NULL DEFAULT NULL,
  `render_duration` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `video_generator_logs_video_id_provider_index` (`video_id`,`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `videos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `scheduler_id` bigint unsigned DEFAULT NULL,
  `idea_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` longtext COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` json DEFAULT NULL,
  `caption` json DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `exports` int unsigned NOT NULL DEFAULT '0',
  `retries` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `videos_campaign_id_index` (`scheduler_id`),
  KEY `videos_idea_id_index` (`idea_id`),
  KEY `videos_user_id_status_type_updated_at_index` (`user_id`,`status`,`type`,`updated_at`),
  KEY `videos_user_id_updated_at_index` (`user_id`,`updated_at`),
  KEY `videos_provider_id_provider_index` (`provider_id`,`provider`),
  CONSTRAINT `videos_idea_id_foreign` FOREIGN KEY (`idea_id`) REFERENCES `ideas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `videos_scheduler_id_foreign` FOREIGN KEY (`scheduler_id`) REFERENCES `schedulers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `videos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `voices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accent` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preview_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `words_per_minute` int DEFAULT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `metadata` json DEFAULT NULL,
  `order` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `voices_user_id_foreign` (`user_id`),
  CONSTRAINT `voices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50001 DROP VIEW IF EXISTS `user_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `user_view` AS select `users`.`id` AS `id`,`users`.`settings` AS `settings`,`users`.`notifications` AS `notifications`,`users`.`mailing_list` AS `mailing_list`,`users`.`email_verified_at` AS `email_verified_at`,`users`.`user_type` AS `user_type`,`users`.`remember_token` AS `remember_token`,`users`.`plan_id` AS `plan_id`,`users`.`subscription_ends_at` AS `subscription_ends_at`,`users`.`trial_ends_at` AS `trial_ends_at`,`users`.`pm_last_four` AS `pm_last_four`,`users`.`pm_type` AS `pm_type`,`users`.`stripe_id` AS `stripe_id`,`users`.`created_at` AS `created_at`,`users`.`updated_at` AS `updated_at`,`users`.`remaining_credit_amount` AS `remaining_credit_amount`,`users`.`monthly_credit_amount` AS `monthly_credit_amount` from `users` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2019_05_03_000001_create_customer_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2019_05_03_000002_create_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2019_05_03_000003_create_subscription_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2022_11_01_000001_create_features_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2022_12_13_193654_create_keywords_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2022_12_13_193740_create_calendars_table copy',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2022_12_13_193741_create_user_login_tracking_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2022_12_17_164949_create_keyword_serarch_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2022_12_20_174244_create_plans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2022_12_20_180513_add_curent_plan_id_column_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2022_12_23_230519_create_contents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2022_12_24_132247_create_content_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2023_01_22_003501_add_active_column_to_plans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2023_01_22_043822_create_user_keyword_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2023_01_29_234155_create_stripe_event_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2023_02_03_021541_add_complete_column_to_calenders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2023_02_16_005045_add_intended_audience_column_to_user_keyword_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2023_02_16_020338_drop_column_intended_audience_from_keywords_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2023_02_16_205420_add_credit_engine_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2023_02_17_180819_create_open_ai_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2023_02_17_195809_create_credit_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2023_02_17_200142_create_credit_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2023_02_23_235454_add_updated_columns_to_contents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2023_03_03_224053_add_morph_columns_to_credit_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2023_03_05_000546_add_duration_column_to_content_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2023_03_05_001007_create_content_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2023_03_06_172743_add_monthly_credit_amount_in_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2023_03_14_075427_alter_time_datatype_in_content_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2023_03_14_134942_remove_video_scripts_columns_from_contents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2023_03_17_132515_create_content_video_script_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2023_03_17_193505_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2023_03_21_132050_create_coupons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2023_03_21_132201_create_redemptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2023_03_21_174115_create_user_feedback_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2023_03_22_150328_add_outline_column_to_content_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2023_03_23_131053_create_synthesia_api_key_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2023_03_23_132304_create_video_generation_status_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2023_03_24_222817_create_content_articles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2023_03_26_014007_add_mailing_list_culumn_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2023_03_27_155607_add_style_and_tone_columns_to_content_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2023_03_28_165458_create_actors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2023_03_28_165726_create_voices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2023_03_28_173551_create_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2023_03_29_153102_create_backgound_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2023_03_29_155249_add_background_to_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2023_03_29_193249_add_flag_to_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2023_03_31_092634_rename_voices_to_video_voices',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2023_04_03_134200_add_script_text_to_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2023_04_03_174914_add_gender_to_video_actors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2023_04_04_131227_add_preferences_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2023_04_04_133310_create_surveys_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2023_04_04_154941_create_webhook_url_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2023_04_04_191751_create_questions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2023_04_04_191818_create_answers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2023_04_17_143236_add_time_to_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2023_04_24_140810_rename_content_videos_table_and_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2023_04_24_140813_rename_fields_in_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2023_04_27_070419_add_primary_key_to_redemptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2023_04_27_070448_add_primary_key_to_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2023_04_27_101444_create_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2023_04_28_100123_rename_webhook_url_fields_and_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2023_05_03_141004_create_metadata_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2023_05_03_215003_increase_title_length_in_calendars_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2023_05_04_095212_create_video_generator_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2023_05_10_143348_drop_synthesia_related_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2023_06_06_183500_create_prompts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2023_06_07_000001_create_pulse_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2023_06_21_084203_create_user_view',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2023_06_25_172345_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2023_07_12_084023_add_extra_credits_field_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2023_07_12_091327_create_credit_purchases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2023_08_11_005729_alter_outline_column_in_contents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2023_08_17_194224_create_social_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2023_08_19_153449_create_speeches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2023_08_22_104344_create_publications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2023_08_22_105128_create_account_publications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2023_09_04_130341_create_publication_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2023_09_20_111115_remove_published_at_from_publications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2023_09_24_233100_add_rregistration_code_column_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2023_09_25_110422_add_keyword_index_to_keyword_search_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2023_09_25_112026_add_content_id_and_duration_index_to_video_scripts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2023_09_25_114904_add_user_id_and_question_id_index_in_answers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2023_09_28_224358_update_length_fields_in_social_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2023_10_03_133256_create_social_channels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2023_10_10_093717_add_user_id_field_to_speeches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2023_10_16_225108_add_social_login_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2023_10_19_223342_remove_different_properties_from_social_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2023_10_19_224709_update_pivot_column_in_account_publications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2023_10_20_095158_create_avatars_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2023_11_01_145411_create_publication_metric_keys_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2023_11_01_152125_create_publication_metric_values_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2023_11_06_172322_create_voices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2023_11_06_190201_change_intended_audience_column_to_user_keyword_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2023_11_10_120205_add_label_column_to_credit_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2023_11_23_081246_add_retries_field_to_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2023_11_24_131738_add_language_column_to_video_scripts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2023_11_24_210107_add_provider_media_id_to_account_publications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2023_11_29_151122_update_duration_column_in_video_scripts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2023_12_04_210337_update_title_column_in_calendars_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2023_12_04_210347_update_title_column_in_contents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2023_12_07_072438_change_credit_events_value_field_data_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2023_12_12_105116_create_tags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2023_12_12_105153_create_taggables_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2023_12_12_105217_create_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2023_12_12_105255_add_source_field_to_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2024_01_17_210449_update_id_in_publication_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2024_01_18_080036_create_purchases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2024_01_18_080109_create_clonables_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2024_01_24_235507_add_generated_date_column_to_publication_metric_values_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2024_01_25_100107_create_footages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2024_01_26_181013_create_real_clones_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2024_01_26_183734_create_generators_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2024_01_28_212956_add_real_clone_id_to_speeches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2024_02_02_081952_create_ideas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2024_02_02_101626_create_campaigns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2024_02_02_102956_create_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2024_02_02_104749_create_keyword_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2024_02_02_110358_create_industries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2024_02_02_112101_create_industry_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2024_02_08_115857_add_synced_at_to_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2024_02_13_101247_rename_keyword_field_in_keywords_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2024_02_16_133549_change_avatar_id_and_provider_id_data_types',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2024_02_22_074356_drop_webhooks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2024_02_22_074406_drop_logins_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2024_02_22_074420_drop_prompts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2024_02_23_070921_drop_video_background_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2024_02_27_133433_remove_scheduled_column_from_publications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2024_03_07_193616_create_facelesses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2024_03_15_082017_drop_unused_tables_and_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2024_03_18_153414_create_assets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2024_03_25_add_metadata_to_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2024_04_03_125821_add_type_to_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2024_04_04_205337_add_channel_specific_token_fields_in_social_channels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2024_04_23_220156_add_post_type_column_to_account_publications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2024_04_30_063632_add_parent_id_field_to_plans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2024_05_02_232514_add_scheduled_column_to_publications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2024_05_07_141158_add_slug_network_fields_to_keywords_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2024_05_07_141327_add_slug_currency_locale_fields_to_ideas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2024_05_13_041536_add_name_field_to_industries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2024_05_22_062937_add_genre_field_to_facelesses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2024_06_15_103417_update_name_column_in_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2024_07_11_140206_create_editor_assets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2024_07_26_135018_add_preference_column_to_footages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2024_07_29_121115_add_promo_code_field_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2024_08_15_033757_create_trackers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2024_08_27_215645_increase_topic_column_length_in_generators_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2024_09_02_220345_add_estimated_duration_to_facelesses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2024_09_12_202741_add_words_per_minute_to_voices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2024_09_13_051705_add_music_id_to_facelesses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2024_09_16_213237_add_order_column_to_voices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2024_09_18_121809_add_missing_foreign_keys',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2024_09_19_035850_add_overall_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2024_09_27_071802_create_faceless_presets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2024_09_29_051552_add_user_id_to_assets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2024_10_01_111037_create_related_topics_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2024_10_09_200336_add_overlay_to_faceless_presets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2024_10_11_065005_add_schedule_id_field_to_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2024_10_16_085303_change_campaings_to_planner_table_name',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2024_10_16_090522_add_scheduler_fields_and_related_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2024_10_16_200329_create_folders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2024_10_16_200335_create_resources_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2024_10_18_083511_add_output_field_to_geneators_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2024_10_21_033044_create_scheduler_occurrences_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2024_10_21_060550_create_job_batches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2024_10_25_050711_add_cancelled_at_field_to_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2024_11_02_112301_add_genre_field_to_faceless_presets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2024_11_08_094941_create_card_fingerprints_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2024_11_14_153655_create_scraper_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2024_12_11_135818_add_public_column_to_ideas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2024_12_13_152308_alter_assets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2024_12_13_215010_create_video_assets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2025_01_08_215820_add_video_watermark_attributes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2025_01_10_130000_add_is_transcribed_field_to_faceless_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2025_01_10_150532_add_exports_field_to_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2025_01_21_133721_add_is_active_field_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2025_01_25_123156_add_batch_field_to_faceless_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2025_01_27_072250_add_index_to_type_field_on_assets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2025_01_29_154033_add_metadata_field_to_schedulers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2025_02_05_081514_add_retries_field_to_assets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2025_02_06_114031_add_language_field_to_related_topics_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2025_02_10_175522_add_video_assets_active_index',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2025_02_07_085820_create_captions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2025_02_07_085834_create_timelines_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2025_02_25_000002_add_fulltext_index_to_assets_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2025_02_26_000001_create_bookmarks_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2025_02_27_071621_remove_unused_faceless_json_fields',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'add_type_column_to_faceless_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2025_02_19_152447_create_publication_aggregates_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2025_03_26_104423_create_suppressions_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2025_03_24_160219_add_uuid_column_in_video_assets_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2025_03_25_113430_add_resource_id_to_faceless_presets_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2025_03_31_112506_add_orientation_field_to_faceless_presets_table',6);
