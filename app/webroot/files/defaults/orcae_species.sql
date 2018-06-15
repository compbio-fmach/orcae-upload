-- phpMyAdmin SQL Dump
-- version 4.2.7.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Mag 23, 2018 alle 14:00
-- Versione del server: 5.6.20
-- PHP Version: 5.5.15
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `orcae_trpee`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
`id` int(11) NOT NULL,
  `locus_id` varchar(64) NOT NULL DEFAULT '',
  `function` text,
  `gene` text,
  `go` text,
  `protein` text,
  `protein_dom` text,
  `protein_hom` text,
  `structure` text,
  `est` text,
  `modification_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `contig`
--

CREATE TABLE IF NOT EXISTS `contig` (
`id` int(11) unsigned NOT NULL,
  `contig_id` varchar(64) NOT NULL DEFAULT '',
  `seq_length` int(10) unsigned NOT NULL DEFAULT '0',
  `AC_nr` varchar(32) DEFAULT NULL,
  `assembly` varchar(14) DEFAULT NULL,
  `seq_md5` varchar(50) NOT NULL DEFAULT ''
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `deep_seq_rna`
--

CREATE TABLE IF NOT EXISTS `deep_seq_rna` (
`id` int(30) NOT NULL,
  `rna_id` varchar(32) NOT NULL,
  `contig_id` varchar(32) NOT NULL,
  `start` int(16) NOT NULL,
  `stop` int(16) NOT NULL,
  `strand` enum('+','-') NOT NULL,
  `count` smallint(3) NOT NULL,
  `sequence` varchar(100) NOT NULL,
  `exp_name` varchar(32) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `est`
--

CREATE TABLE IF NOT EXISTS `est` (
`id` int(11) unsigned NOT NULL,
  `locus_id` varchar(64) NOT NULL DEFAULT '',
  `est_id` varchar(64) NOT NULL DEFAULT '',
  `bitscore` smallint(6) unsigned NOT NULL DEFAULT '0',
  `evalue` double NOT NULL DEFAULT '0',
  `identity` smallint(6) unsigned NOT NULL DEFAULT '0',
  `sense` enum('','5','3') NOT NULL DEFAULT '',
  `map_struct` text NOT NULL,
  `strand` set('+','-') NOT NULL DEFAULT '',
  `start` int(11) NOT NULL DEFAULT '0',
  `stop` int(11) NOT NULL DEFAULT '0',
  `contig_id` varchar(64) NOT NULL,
  `ext_ref` varchar(64) DEFAULT NULL,
  `ext_database` varchar(64) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `est_usage` enum('','Y','N') NOT NULL DEFAULT '',
  `modification_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `function`
--

CREATE TABLE IF NOT EXISTS `function` (
`id` int(11) unsigned NOT NULL,
  `locus_id` varchar(64) NOT NULL DEFAULT '',
  `given_name` varchar(16) DEFAULT NULL,
  `name_synonyms` text NOT NULL,
  `definition` varchar(250) DEFAULT NULL,
  `description` text,
  `pubmed_id` varchar(160) DEFAULT NULL,
  `quality` enum('1','2','3','4','5') NOT NULL DEFAULT '1',
  `ec_number` varchar(50) DEFAULT NULL,
  `KOG_id` varchar(8) DEFAULT NULL,
  `modification_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `gene`
--

CREATE TABLE IF NOT EXISTS `gene` (
`id` int(11) unsigned NOT NULL,
  `locus_id` varchar(64) NOT NULL DEFAULT '',
  `contig` varchar(64) NOT NULL DEFAULT '',
  `ref_id` varchar(64) NOT NULL,
  `type` varchar(16) NOT NULL DEFAULT 'mRNA',
  `sequence` text NOT NULL,
  `seq_md5` varchar(50) NOT NULL DEFAULT '',
  `modification_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `go_terms`
--

CREATE TABLE IF NOT EXISTS `go_terms` (
`id` int(11) NOT NULL,
  `locus_id` varchar(64) NOT NULL DEFAULT '',
  `go_terms` text NOT NULL,
  `modification_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `history`
--

CREATE TABLE IF NOT EXISTS `history` (
`id` int(11) unsigned NOT NULL,
  `locus_id` varchar(64) NOT NULL DEFAULT '',
  `annotator_id` int(10) NOT NULL DEFAULT '0',
  `modification_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `locked`
--

CREATE TABLE IF NOT EXISTS `locked` (
`id` int(11) unsigned NOT NULL,
  `locus_id` varchar(64) NOT NULL DEFAULT '',
  `status` enum('active','inactive','update','upload','download','error') NOT NULL DEFAULT 'active',
  `annotator_id` int(10) unsigned NOT NULL DEFAULT '0',
  `modification_date` timestamp NULL DEFAULT '2008-10-26 11:00:00'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `markers`
--

CREATE TABLE IF NOT EXISTS `markers` (
`id` int(11) unsigned NOT NULL,
  `marker_id` varchar(20) NOT NULL,
  `contig_id` varchar(64) NOT NULL,
  `start` int(11) NOT NULL,
  `end` int(11) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `experiment` varchar(20) DEFAULT NULL,
  `annotator_id` int(10) unsigned DEFAULT NULL,
  `modification_date` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `protein`
--

CREATE TABLE IF NOT EXISTS `protein` (
`id` int(11) unsigned NOT NULL,
  `locus_id` varchar(64) NOT NULL DEFAULT '',
  `signal_peptide` text NOT NULL,
  `destination` varchar(150) DEFAULT NULL,
  `destination_score` varchar(150) DEFAULT NULL,
  `length` int(5) unsigned NOT NULL DEFAULT '0',
  `modification_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `protein_aln`
--

CREATE TABLE IF NOT EXISTS `protein_aln` (
`id` int(11) NOT NULL,
  `locus_id` varchar(64) NOT NULL,
  `protDB` varchar(30) NOT NULL DEFAULT 'n/a',
  `aln` longtext,
  `modification_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `protein_domain`
--

CREATE TABLE IF NOT EXISTS `protein_domain` (
`id` int(11) unsigned NOT NULL,
  `locus_id` varchar(64) NOT NULL DEFAULT '',
  `ext_ref` varchar(32) DEFAULT NULL,
  `ext_database` varchar(32) DEFAULT NULL,
  `motif_name` varchar(50) NOT NULL DEFAULT '',
  `from` smallint(6) unsigned NOT NULL DEFAULT '0',
  `to` smallint(6) unsigned NOT NULL DEFAULT '0',
  `align_score` float NOT NULL DEFAULT '0',
  `similar_score` float NOT NULL DEFAULT '0',
  `iprID` varchar(10) DEFAULT NULL,
  `description` varchar(100) NOT NULL DEFAULT '',
  `modification_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `protein_homolog`
--

CREATE TABLE IF NOT EXISTS `protein_homolog` (
`id` int(11) unsigned NOT NULL,
  `locus_id` varchar(64) NOT NULL DEFAULT '0',
  `ext_ref` varchar(32) DEFAULT NULL,
  `ext_database` varchar(32) DEFAULT NULL,
  `bitscore` smallint(6) unsigned NOT NULL DEFAULT '0',
  `evalue` double NOT NULL DEFAULT '0',
  `aln_stats` varchar(100) NOT NULL DEFAULT 'n/a',
  `hit_length` mediumint(9) unsigned NOT NULL DEFAULT '0',
  `identities` mediumint(9) unsigned DEFAULT '0',
  `similarities` mediumint(9) unsigned DEFAULT '0',
  `gaps` mediumint(9) unsigned DEFAULT '0',
  `from` smallint(6) unsigned NOT NULL DEFAULT '0',
  `to` smallint(6) unsigned NOT NULL DEFAULT '0',
  `description` varchar(250) DEFAULT NULL,
  `modification_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `repeats`
--

CREATE TABLE IF NOT EXISTS `repeats` (
`id` int(11) NOT NULL,
  `contig_id` varchar(64) NOT NULL,
  `start` int(11) NOT NULL DEFAULT '0',
  `end` int(11) NOT NULL DEFAULT '0',
  `description` varchar(150) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `structure`
--

CREATE TABLE IF NOT EXISTS `structure` (
`id` int(11) unsigned NOT NULL,
  `locus_id` varchar(64) NOT NULL DEFAULT '',
  `structure` text,
  `START` int(10) unsigned NOT NULL DEFAULT '0',
  `STOP` int(10) unsigned NOT NULL DEFAULT '0',
  `number_of_exons` smallint(5) unsigned NOT NULL DEFAULT '0',
  `strand` enum('','+','-') NOT NULL DEFAULT '',
  `quality` enum('1','2','3','4','5') NOT NULL DEFAULT '1',
  `modification_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

-- --------------------------------------------------------

--
-- Struttura della tabella `tiling_array`
--

CREATE TABLE IF NOT EXISTS `tiling_array` (
`id` int(11) unsigned NOT NULL,
  `contig_id` varchar(64) NOT NULL DEFAULT '',
  `P_start` int(11) NOT NULL DEFAULT '0',
  `P_stop` int(11) NOT NULL DEFAULT '0',
  `exp_value` float NOT NULL DEFAULT '0',
  `control` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
 ADD PRIMARY KEY (`id`), ADD KEY `locus_id` (`locus_id`);

--
-- Indexes for table `contig`
--
ALTER TABLE `contig`
 ADD PRIMARY KEY (`id`), ADD KEY `contig_id` (`contig_id`);

--
-- Indexes for table `deep_seq_rna`
--
ALTER TABLE `deep_seq_rna`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `est`
--
ALTER TABLE `est`
 ADD PRIMARY KEY (`id`), ADD KEY `locus_id` (`locus_id`), ADD KEY `modification_date` (`modification_date`), ADD KEY `start` (`start`), ADD KEY `stop` (`stop`);

--
-- Indexes for table `function`
--
ALTER TABLE `function`
 ADD PRIMARY KEY (`id`), ADD KEY `locus_id` (`locus_id`), ADD KEY `modification_date` (`modification_date`);

--
-- Indexes for table `gene`
--
ALTER TABLE `gene`
 ADD PRIMARY KEY (`id`), ADD KEY `locus_id` (`locus_id`), ADD KEY `modification_date` (`modification_date`), ADD KEY `ref_id` (`ref_id`);

--
-- Indexes for table `go_terms`
--
ALTER TABLE `go_terms`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `history`
--
ALTER TABLE `history`
 ADD PRIMARY KEY (`id`), ADD KEY `locus_id` (`locus_id`), ADD KEY `modification_date` (`modification_date`), ADD KEY `annotator_id` (`annotator_id`);

--
-- Indexes for table `locked`
--
ALTER TABLE `locked`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `locus_id_2` (`locus_id`), ADD KEY `locus_id` (`locus_id`);

--
-- Indexes for table `markers`
--
ALTER TABLE `markers`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `protein`
--
ALTER TABLE `protein`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`locus_id`), ADD KEY `modification_date` (`modification_date`);

--
-- Indexes for table `protein_aln`
--
ALTER TABLE `protein_aln`
 ADD PRIMARY KEY (`id`), ADD KEY `locus_id` (`locus_id`);

--
-- Indexes for table `protein_domain`
--
ALTER TABLE `protein_domain`
 ADD PRIMARY KEY (`id`), ADD KEY `locus_id` (`locus_id`), ADD KEY `modification_date` (`modification_date`);

--
-- Indexes for table `protein_homolog`
--
ALTER TABLE `protein_homolog`
 ADD PRIMARY KEY (`id`), ADD KEY `locus_id` (`locus_id`), ADD KEY `modification_date` (`modification_date`), ADD KEY `description` (`description`);

--
-- Indexes for table `repeats`
--
ALTER TABLE `repeats`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `structure`
--
ALTER TABLE `structure`
 ADD PRIMARY KEY (`id`), ADD KEY `locus_id` (`locus_id`), ADD KEY `modification_date` (`modification_date`), ADD KEY `START` (`START`);

--
-- Indexes for table `tiling_array`
--
ALTER TABLE `tiling_array`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `contig`
--
ALTER TABLE `contig`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `deep_seq_rna`
--
ALTER TABLE `deep_seq_rna`
MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `est`
--
ALTER TABLE `est`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `function`
--
ALTER TABLE `function`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `gene`
--
ALTER TABLE `gene`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `go_terms`
--
ALTER TABLE `go_terms`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `history`
--
ALTER TABLE `history`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `locked`
--
ALTER TABLE `locked`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `markers`
--
ALTER TABLE `markers`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `protein`
--
ALTER TABLE `protein`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `protein_aln`
--
ALTER TABLE `protein_aln`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `protein_domain`
--
ALTER TABLE `protein_domain`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `protein_homolog`
--
ALTER TABLE `protein_homolog`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `repeats`
--
ALTER TABLE `repeats`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `structure`
--
ALTER TABLE `structure`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tiling_array`
--
ALTER TABLE `tiling_array`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
