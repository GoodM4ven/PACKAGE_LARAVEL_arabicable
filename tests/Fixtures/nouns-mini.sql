CREATE TABLE nouns (
  id int(11) NOT NULL UNIQUE,
  vocalized varchar(30) DEFAULT NULL,
  unvocalized varchar(30) DEFAULT NULL,
  wordtype varchar(30) DEFAULT NULL,
  root varchar(30) DEFAULT NULL,
  normalized varchar(30) NOT NULL,
  stamped varchar(30) NOT NULL,
  original varchar(30) DEFAULT NULL,
  mankous varchar(30) DEFAULT NULL,
  feminable varchar(30) DEFAULT NULL,
  number varchar(30) DEFAULT NULL,
  dualable varchar(30) DEFAULT NULL,
  masculin_plural varchar(30) DEFAULT NULL,
  feminin_plural varchar(30) DEFAULT NULL,
  broken_plural varchar(30) DEFAULT NULL,
  mamnou3_sarf varchar(30) DEFAULT NULL,
  relative varchar(30) DEFAULT NULL,
  w_suffix varchar(30) DEFAULT NULL,
  hm_suffix varchar(30) DEFAULT NULL,
  kal_prefix varchar(30) DEFAULT NULL,
  ha_suffix varchar(30) DEFAULT NULL,
  k_suffix varchar(30) DEFAULT NULL,
  annex varchar(30) DEFAULT NULL,
  definition text,
  note text
);
INSERT INTO nouns (id, vocalized, unvocalized, wordtype, root, normalized, stamped, original, mankous, feminable, number, dualable, masculin_plural, feminin_plural, broken_plural, mamnou3_sarf, relative, w_suffix, hm_suffix, kal_prefix, ha_suffix, k_suffix, annex, definition, note) VALUES(1, 'كِتَابٌ', 'كتاب', 'اسم', 'كتب', 'كتاب', 'كتب', '', '', 'Ta', 'مفرد', 'DnT', '', '', 'كُتُبٌ', '', '', '', '', '', '', '', '', '', '');
INSERT INTO nouns (id, vocalized, unvocalized, wordtype, root, normalized, stamped, original, mankous, feminable, number, dualable, masculin_plural, feminin_plural, broken_plural, mamnou3_sarf, relative, w_suffix, hm_suffix, kal_prefix, ha_suffix, k_suffix, annex, definition, note) VALUES(2, 'قَلَمٌ', 'قلم', 'اسم', 'قلم', 'قلم', 'قلم', '', '', 'Ta', 'مفرد', 'DnT', '', '', 'أَقْلَامٌ', '', '', '', '', '', '', '', '', '', '');
