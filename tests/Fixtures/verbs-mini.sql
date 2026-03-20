create table verbs (
  id int unique not null,
  vocalized varchar(30) not null,
  unvocalized varchar(30) not null,
  root varchar(30)
);
insert into verbs values ('1','يَضْرِبُ','يضرب','ضرب');
insert into verbs values ('2','يَعْلَمُ','يعلم','علم');
