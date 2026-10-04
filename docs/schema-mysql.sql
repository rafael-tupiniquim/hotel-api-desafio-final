-- Schema da Hotel API em MySQL, equivalente às migrations do Laravel.
-- Pode ser importado no MySQL Workbench (File > Import > Reverse Engineer MySQL Create Script...)
-- para gerar o diagrama EER, sem precisar de um servidor em execução.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Pontos do mapa da cidade (hotel, restaurante ou cruzamento).
-- Vem primeiro porque "hotels" referencia esta tabela.
CREATE TABLE city_nodes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('hotel', 'restaurant', 'intersection') NOT NULL DEFAULT 'intersection',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hoteis (ID vem do XML fornecido, por isso NAO e AUTO_INCREMENT).
CREATE TABLE hotels (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    city_node_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_hotels_city_node FOREIGN KEY (city_node_id) REFERENCES city_nodes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quartos/acomodacoes (ID vem do XML).
CREATE TABLE rooms (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    hotel_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_rooms_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reservas (ID vem do XML). Cada reserva e UM quarto + UM periodo.
CREATE TABLE reserves (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    hotel_id BIGINT UNSIGNED NOT NULL,
    room_id BIGINT UNSIGNED NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_reserves_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    CONSTRAINT fk_reserves_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_room_period (room_id, check_in, check_out)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hospedes de uma reserva.
CREATE TABLE guests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reserve_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NULL,
    phone VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_guests_reserve FOREIGN KEY (reserve_id) REFERENCES reserves(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valor cobrado em cada diaria da estadia.
CREATE TABLE dailies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reserve_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_dailies_reserve FOREIGN KEY (reserve_id) REFERENCES reserves(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagamentos registrados para uma reserva.
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reserve_id BIGINT UNSIGNED NOT NULL,
    method INT UNSIGNED NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_payments_reserve FOREIGN KEY (reserve_id) REFERENCES reserves(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ruas conectando dois pontos do mapa (o grafo que o Dijkstra percorre).
CREATE TABLE city_edges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_node_id BIGINT UNSIGNED NOT NULL,
    to_node_id BIGINT UNSIGNED NOT NULL,
    distance_km DECIMAL(6, 2) NOT NULL,
    bidirectional TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_edges_from FOREIGN KEY (from_node_id) REFERENCES city_nodes(id) ON DELETE CASCADE,
    CONSTRAINT fk_edges_to FOREIGN KEY (to_node_id) REFERENCES city_nodes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restaurantes (feature de recomendacao via Dijkstra).
CREATE TABLE restaurants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    city_node_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    cuisine VARCHAR(255) NOT NULL,
    rating DECIMAL(2, 1) NOT NULL DEFAULT 0,
    price_range VARCHAR(3) NOT NULL DEFAULT '$$',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_restaurants_node FOREIGN KEY (city_node_id) REFERENCES city_nodes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
