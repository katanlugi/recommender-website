CreateMoviesTable: create table `movies` (`id` int unsigned not null auto_increment primary key, `imdbId` varchar(255) not null, `tmdbId` varchar(255) not null, `title` varchar(255) not null, `genres` varchar(255) not null, `releaseDate` date not null, `metascore` varchar(255) null, `imdbRating` varchar(255) not null, `imdbVotes` double(8, 2) not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate utf8mb4_unicode_ci engine = InnoDB
CreateMoviesTable: alter table `movies` add unique `movies_imdbid_unique`(`imdbId`)
CreateMoviesTable: alter table `movies` add unique `movies_tmdbid_unique`(`tmdbId`)
CreateRatingsTable: create table `ratings` (`userId` int not null, `movieId` int not null, `rating` varchar(255) not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate utf8mb4_unicode_ci engine = InnoDB
CreateRatingsTable: alter table `ratings` add constraint `ratings_userid_foreign` foreign key (`userId`) references `users` (`id`)
CreateRatingsTable: alter table `ratings` add constraint `ratings_movieid_foreign` foreign key (`movieId`) references `movies` (`id`)
CreateRecommendationsTable: create table `recommendations` (`userId` int not null, `movieId` int not null, `value` varchar(255) not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate utf8mb4_unicode_ci engine = InnoDB
CreateRecommendationsTable: alter table `recommendations` add constraint `recommendations_userid_foreign` foreign key (`userId`) references `users` (`id`)
CreateRecommendationsTable: alter table `recommendations` add constraint `recommendations_movieid_foreign` foreign key (`movieId`) references `movies` (`id`)
CreateTagsTable: create table `tags` (`userId` int not null, `movieId` int not null, `tag` varchar(255) not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate utf8mb4_unicode_ci engine = InnoDB
CreateTagsTable: alter table `tags` add constraint `tags_userid_foreign` foreign key (`userId`) references `users` (`id`)
CreateTagsTable: alter table `tags` add constraint `tags_movieid_foreign` foreign key (`movieId`) references `movies` (`id`)
CreateLinksTable: create table `links` (`movieId` int not null, `imdbId` varchar(255) not null, `tmdbId` varchar(255) not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate utf8mb4_unicode_ci engine = InnoDB
CreateLinksTable: alter table `links` add constraint `links_movieid_foreign` foreign key (`movieId`) references `movies` (`id`)
CreateJobsTable: create table `jobs` (`id` bigint unsigned not null auto_increment primary key, `queue` varchar(255) not null, `payload` longtext not null, `attempts` tinyint unsigned not null, `reserved_at` int unsigned null, `available_at` int unsigned not null, `created_at` int unsigned not null) default character set utf8mb4 collate utf8mb4_unicode_ci engine = InnoDB
CreateJobsTable: alter table `jobs` add index `jobs_queue_index`(`queue`)
