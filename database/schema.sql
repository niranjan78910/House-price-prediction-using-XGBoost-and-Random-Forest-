CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
email VARCHAR(100) UNIQUE NOT NULL,
password VARCHAR(255) NOT NULL,
name VARCHAR(100) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE properties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  agent_id INT NOT NULL DEFAULT 0,
  address VARCHAR(200) NOT NULL,
  city VARCHAR(100) NOT NULL,
  postcode VARCHAR(20) NOT NULL,
  postcode_area VARCHAR(10) NOT NULL,
  bedrooms INT NOT NULL,
  bathrooms INT NOT NULL,
  floor_area DECIMAL(8,2) NULL,
  freehold CHAR(1) NOT NULL DEFAULT 'F',
  property_type VARCHAR(50) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'lead',
  asking_price DECIMAL(12,2) NULL,
  predicted_price DECIMAL(12,2) NULL,
  agent_notes TEXT NULL,
  is_published TINYINT NOT NULL DEFAULT 0,
  public_slug VARCHAR(140) UNIQUE NULL,
  public_description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE property_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_property_images_property_id (property_id),
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);
CREATE TABLE owners (
id INT AUTO_INCREMENT PRIMARY KEY,
property_id INT NOT NULL,
name VARCHAR(100) NOT NULL,
email VARCHAR(100) NULL,
phone VARCHAR(20) NULL
);
CREATE TABLE followups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT NOT NULL,
  task_type VARCHAR(100) NOT NULL,
  notes TEXT NULL,
  due_date DATE NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_followups_property_id (property_id),

  FOREIGN KEY (property_id)
    REFERENCES properties(id)
    ON DELETE CASCADE
);
CREATE TABLE predictions (
id INT AUTO_INCREMENT PRIMARY KEY,
property_id INT NOT NULL,
predicted_price DECIMAL(12,2) NOT NULL,
model_used VARCHAR(50) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  agency VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  active_listings INT NOT NULL,
  next_step VARCHAR(30) NOT NULL,
  message TEXT NOT NULL,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE inquiries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT NOT NULL,
  agent_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NULL,
  message TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE
);
