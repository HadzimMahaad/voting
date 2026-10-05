CREATE DATABASE IF NOT EXISTS voting CHARACTER SET utf8mb4; USE voting;
CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(80),voter_id VARCHAR(40) DEFAULT NULL UNIQUE,email VARCHAR(120) UNIQUE,password VARCHAR(255),is_admin TINYINT DEFAULT 0,is_staff TINYINT NOT NULL DEFAULT 0,approved TINYINT NOT NULL DEFAULT 1);
CREATE TABLE elections(id INT AUTO_INCREMENT PRIMARY KEY,title VARCHAR(200),status ENUM('draft','open','closed') DEFAULT 'draft');
CREATE TABLE organizations(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(200) NOT NULL UNIQUE,logo_path VARCHAR(255) DEFAULT NULL);
CREATE TABLE members(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,organization_id INT DEFAULT NULL,image_path VARCHAR(255) DEFAULT NULL,UNIQUE KEY uq_members_organization_name(organization_id,name),FOREIGN KEY(organization_id) REFERENCES organizations(id) ON DELETE SET NULL);
CREATE TABLE candidates(id INT AUTO_INCREMENT PRIMARY KEY,election_id INT,name VARCHAR(120),organization_id INT DEFAULT NULL,image_path VARCHAR(255) DEFAULT NULL,member_id INT DEFAULT NULL,UNIQUE KEY uq_candidates_election_member(election_id,member_id),FOREIGN KEY(election_id) REFERENCES elections(id) ON DELETE CASCADE,FOREIGN KEY(organization_id) REFERENCES organizations(id) ON DELETE SET NULL,FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE SET NULL);
-- Secret ballot: who voted and what was cast are stored separately, with no link between them.
CREATE TABLE voted(election_id INT,user_id INT,PRIMARY KEY(election_id,user_id));
CREATE TABLE anonymous_voted(election_id INT NOT NULL,voter_token CHAR(64) NOT NULL,PRIMARY KEY(election_id,voter_token),FOREIGN KEY(election_id) REFERENCES elections(id) ON DELETE CASCADE);
CREATE TABLE ballots(id INT AUTO_INCREMENT PRIMARY KEY,election_id INT,candidate_id INT);
