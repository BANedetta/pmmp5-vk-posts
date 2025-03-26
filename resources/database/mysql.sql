-- #! mysql

-- #{ vk_table
	-- #{ init
		CREATE TABLE IF NOT EXISTS vk_posts (
			post_id INT PRIMARY KEY,
			banned VARCHAR(255) UNIQUE,
			data TEXT
		);
	-- #}
-- #}

-- #{ vk_data
	-- #{ get_data_by_banned
		-- # :banned string
		SELECT * FROM vk_posts WHERE banned = :banned;
	-- #}

	-- #{ get_data_by_id
		-- # :post_id int
		SELECT * FROM vk_posts WHERE post_id = :post_id;
	-- #}

	-- #{ add
		-- # :post_id int
		-- # :banned string
		-- # :data string
		INSERT INTO vk_posts (post_id, banned, data)
		VALUES (:post_id, :banned, :data)
		ON DUPLICATE KEY UPDATE
			post_id = VALUES(post_id),
			data = VALUES(data);
	-- #}

	-- #{ remove_post_by_banned
		-- # :banned string
		DELETE FROM vk_posts WHERE banned = :banned;
	-- #}

	-- #{ remove_post_by_id
		-- # :post_id int
		DELETE FROM vk_posts WHERE post_id = :post_id;
	-- #}
-- #}