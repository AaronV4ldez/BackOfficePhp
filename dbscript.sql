-- run as root
create database liex charset 'UTF8';

grant all privileges on liex.* to liex_user identified by 'line@Infernal656';

-- run as 'liex_user'
CREATE TABLE
  users (
    id int(11) NOT NULL AUTO_INCREMENT,
    fullname varchar(128) NOT NULL,
    userlogin varchar(128) NOT NULL,
    password_hash varchar(255) NOT NULL,
    phone varchar(64) NOT NULL,
    account_verified int(11) NOT NULL DEFAULT '0',
    sentri_number varchar(64) DEFAULT NULL,
    sentri_exp_date date DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `userlogin` (`userlogin`)
  ) DEFAULT CHARSET = utf8;

create table
  user_activations (
    id int not null auto_increment primary key,
    id_user int not null references users(id),
    creation_dt datetime not null DEFAULT CURRENT_TIMESTAMP,
    activation_code varchar(32) not null,
    activation_dt datetime,
    last_try_dt datetime,
    number_of_tries int default 0
  );

create index idx_useract_user on user_activations(id_user);

-- 1=mobile user, 2=operator, 3=supervisor, 4=manager
alter table
  users
add
  usertype int not null default 1;

create table
  procedures (
    id int not null primary key,
    description varchar(256) not null
  );

insert into
  procedures(id, description)
values
  (1, 'Solicitud de inscripcion'),
  (2, 'Inscripcion en otro puente'),
  (3, 'Solicitud de cambio de vehiculo'),
  (4, 'Solicitud de transferencia de saldo'),
  (5, 'Solicitud de baja de vehiculo');

create table
  procedure_status (
    id int not null primary key,
    status_desc varchar(128) not null
  );

insert into
  procedure_status(id, status_desc)
values
  (0, 'Proceso cancelado'),
  (1, 'Proceso iniciado'),
  (2, 'Asignado a asesor'),
  (3, 'Documento(s) rechazado(s)'),
  (4, 'Cita agendada'),
  (5, 'Terminado');

--depreca?
create table
  user_procedures (
    id int not null auto_increment primary key,
    id_user int not null references users(id),
    id_procedure int not null references procedures(id),
    id_procedure_status int not null references procedure_status(id),
    start_dt datetime,
    finish_dt datetime,
    last_update_dt datetime,
    index(id_user),
    index(id_procedure)
  );

create table
  proc01 (
    id int not null auto_increment primary key,
    -- base begin
    id_user int not null references users(id),
    id_user_operator int references users(id),
    id_procedure int not null references procedures(id),
    id_procedure_status int not null references procedure_status(id),
    start_dt datetime,
    last_update_dt datetime,
    finish_dt datetime,
    -- base end
    dom_calle varchar(128) not null,
    dom_numero_ext varchar(32) not null,
    dom_colonia varchar(128) not null,
    dom_ciudad varchar(128) not null,
    dom_estado varchar(128) not null,
    dom_cp varchar(16) not null,
    fac_razon_social varchar(16) not null,
    fac_rfc varchar(16) not null,
    fac_dom_fiscal varchar(16) not null,
    fac_email varchar(16) not null,
    fac_telefono varchar(16) not null,
    veh_marca varchar(32) not null,
    veh_modelo varchar(32) not null,
    veh_color varchar(32) not null,
    veh_anio int not null,
    veh_placas varchar(32) not null,
    veh_origen varchar(64) not null,
    conv_saldo decimal(12, 2) not null,
    conv_anualidad int not null,
    -- base idx begin
    index(id_user),
    index(id_user_operator),
    index(id_procedure),
    index(id_procedure_status) -- base idx end
  );

create table
  user_proc_updates (
    id int not null auto_increment primary key,
    id_procedure int not null references user_procedures(id),
    id_user_created int not null references users(id),
    -- following points to a column on each procXX table, cant be enforced FK 
    id_ref int not null,
    -- user that generated the update
    update_desc varchar(512) not null,
    update_dt datetime,
    index(id_procedure),
    index(id_ref),
    index(id_user_created)
  );

delimiter / / drop procedure if exists sp_add_proc_update / / create procedure sp_add_proc_update(
  in p_id_proc int,
  in p_id_user_created int,
  in p_id_ref int,
  in p_upd_text varchar(512)
) begin
insert into
  user_proc_updates(
    id_procedure,
    id_user_created,
    id_ref,
    update_desc,
    update_dt
  )
values
  (
    p_id_proc,
    p_id_user_created,
    p_id_ref,
    p_upd_text,
    CURRENT_TIMESTAMP
  );

end / / delimiter;

-- create
-- or replace view vw_user_procedures as
-- SELECT
--   up.id as id_user_procedure,
--   up.id_procedure,
--   p.description as procedurename,
--   up.id_user,
--   up.start_dt,
--   up.last_update_dt,
--   up.finish_dt,
--   ps.status_desc as proc_status
-- from
--   proc01 up
--   join procedures p on p.id = up.id_procedure
--   join procedure_status ps on ps.id = up.id_procedure_status;

create
or replace view vw_user_proc01 as
SELECT
  p01.*, 
  u1.fullname usuario_nombre, u1.userlogin usuario_email,
  -- u2.fullname operador_nombre, u2.userlogin operador_email,
  procs.description tramite,
  ps.status_desc tramite_status 
from
  proc01 p01
  join users u1 on u1.id = p01.id_user
  join procedures procs on procs.id = p01.id_procedure
  left join procedure_status ps on ps.id = p01.id_procedure_status
 -- left outer join users u2 on u2.id = p01.id_user_operator
    ;


create table
  proc02 (
    id int not null auto_increment primary key,
    -- base begin
    id_user int not null references users(id),
    id_user_operator int references users(id),
    id_procedure int not null references procedures(id),
    id_procedure_status int not null references procedure_status(id),
    start_dt datetime,
    last_update_dt datetime,
    finish_dt datetime,
    -- base end
    dom_calle varchar(128) not null,
    dom_numero_ext varchar(32) not null,
    dom_colonia varchar(128) not null,
    dom_ciudad varchar(128) not null,
    dom_estado varchar(128) not null,
    dom_cp varchar(16) not null,
    fac_razon_social varchar(16) not null,
    fac_rfc varchar(16) not null,
    fac_dom_fiscal varchar(16) not null,
    fac_email varchar(16) not null,
    fac_telefono varchar(16) not null,
    veh_marca varchar(32) not null,
    veh_modelo varchar(32) not null,
    veh_color varchar(32) not null,
    veh_anio int not null,
    veh_placas varchar(32) not null,
    veh_origen varchar(64) not null,
    conv_saldo decimal(12, 2) not null,
    conv_anualidad int not null,
    -- base idx begin
    index(id_user),
    index(id_user_operator),
    index(id_procedure),
    index(id_procedure_status) -- base idx end
  );    

create
or replace view vw_user_proc02 as
SELECT
  p02.*, 
  u1.fullname usuario_nombre, u1.userlogin usuario_email,
  u2.fullname operador_nombre, u2.userlogin operador_email,
  procs.description tramite,
  ps.status_desc tramite_status 
from
  proc02 p02
  join users u1 on u1.id = p02.id_user
  join procedures procs on procs.id = p02.id_procedure
  join procedure_status ps on ps.id = p02.id_procedure_status
  left outer join users u2 on u2.id = p02.id_user_operator
    ;




create table filetypes (
  id int not null primary key,
  description varchar(128) not null
);

insert into filetypes values
(1, 'Sentri'),
(2, 'ID oficial (frente)'),
(3, 'ID oficial (reverso)'),
(4, 'Tarjeta de circulacion');

drop view vw_file_info;
drop table file_info;

create table file_info (
  id int not null auto_increment primary key,
  file_name varchar(256) not null,
  id_proc int not null,
  id_procedure int not null references procedures(id),
  id_user int not null references users(id),
  id_file_type int not null references filetypes(id),
  upload_dt datetime not null,
  upload_count int not null default 0,
  file_status int not null default 0, -- 0=new waiting for auth, 1=rejected, 2=approved
  comment varchar(512),
  approved_dt datetime,
  id_user_operator int references users(id),
  index(id_proc),
  index(id_file_type),
  index(file_status)
);

create or replace view vw_file_info
as
select 
  fi.*, case when fi.file_status = 0 then 'Esperando revision' when fi.file_status=1 then 'Rechazado' when fi.file_status=2 then 'Aprobado' end as status, ft.description file_type_desc
from file_info fi
join filetypes ft on ft.id = fi.id_file_type
join vw_user_procs up on up.id_procedure = fi.id_procedure and up.id = fi.id_proc
;

drop view vw_user_proc03;
drop table proc03;

create table proc03 (
    id int not null auto_increment primary key,
    -- base begin
    id_user int not null references users(id),
    id_user_operator int references users(id),
    id_procedure int not null references procedures(id),
    id_procedure_status int not null references procedure_status(id),
    start_dt datetime,
    last_update_dt datetime,
    finish_dt datetime,
    -- base end
    num_tag varchar(128) not null,
    fac_razon_social varchar(16) not null,
    fac_rfc varchar(16) not null,
    fac_dom_fiscal varchar(16) not null,
    fac_email varchar(16) not null,
    fac_telefono varchar(16) not null,
    veh1_marca varchar(32) not null,
    veh1_modelo varchar(32) not null,
    veh1_color varchar(32) not null,
    veh1_anio int not null,
    veh1_placas varchar(32) not null,
    veh1_origen varchar(64) not null,
    veh2_marca varchar(32) not null,
    veh2_modelo varchar(32) not null,
    veh2_color varchar(32) not null,
    veh2_anio int not null,
    veh2_placas varchar(32) not null,
    veh2_origen varchar(64) not null,

    -- base idx begin
    index(id_user),
    index(id_user_operator),
    index(id_procedure),
    index(id_procedure_status) -- base idx end    
);

create
or replace view vw_user_proc03 as
SELECT
  p03.*, 
  u1.fullname usuario_nombre, u1.userlogin usuario_email,
  u2.fullname operador_nombre, u2.userlogin operador_email,
  procs.description tramite,
  ps.status_desc tramite_status 
from
  proc03 p03
  join users u1 on u1.id = p03.id_user
  join procedures procs on procs.id = p03.id_procedure
  join procedure_status ps on ps.id = p03.id_procedure_status
  left outer join users u2 on u2.id = p03.id_user_operator
    ;



create table proc04 (
    id int not null auto_increment primary key,
    -- base begin
    id_user int not null references users(id),
    id_user_operator int references users(id),
    id_procedure int not null references procedures(id),
    id_procedure_status int not null references procedure_status(id),
    start_dt datetime,
    last_update_dt datetime,
    finish_dt datetime,
    -- base end

    num_tag1 varchar(128) not null,
    veh1_marca varchar(32) not null,
    veh1_modelo varchar(32) not null,
    veh1_color varchar(32) not null,
    veh1_anio int not null,
    veh1_placas varchar(32) not null,
    veh1_origen varchar(64) not null,

    num_tag2 varchar(128) not null,
    veh2_marca varchar(32) not null,
    veh2_modelo varchar(32) not null,
    veh2_color varchar(32) not null,
    veh2_anio int not null,
    veh2_placas varchar(32) not null,
    veh2_origen varchar(64) not null,

    -- base idx begin
    index(id_user),
    index(id_user_operator),
    index(id_procedure),
    index(id_procedure_status) -- base idx end    
);

create
or replace view vw_user_proc04 as
SELECT
  p04.*, 
  u1.fullname usuario_nombre, u1.userlogin usuario_email,
  u2.fullname operador_nombre, u2.userlogin operador_email,
  procs.description tramite,
  ps.status_desc tramite_status 
from
  proc04 p04
  join users u1 on u1.id = p04.id_user
  join procedures procs on procs.id = p04.id_procedure
  join procedure_status ps on ps.id = p04.id_procedure_status
  left outer join users u2 on u2.id = p04.id_user_operator
    ;


drop table proc05;  

create table proc05 (
    id int not null auto_increment primary key,
    -- base begin
    id_user int not null references users(id),
    id_user_operator int references users(id),
    id_procedure int not null references procedures(id),
    id_procedure_status int not null references procedure_status(id),
    start_dt datetime,
    last_update_dt datetime,
    finish_dt datetime,
    -- base end

    num_tag varchar(128) not null,
    veh_marca varchar(32) not null,
    veh_modelo varchar(32) not null,
    veh_color varchar(32) not null,
    veh_anio int not null,
    veh_placas varchar(32) not null,
    veh_origen varchar(64) not null,
    veh_adscrip_zaragoza int not null,
    veh_adscrip_lerdo int not null,
    motivo varchar(256) not null,

    -- base idx begin
    index(id_user),
    index(id_user_operator),
    index(id_procedure),
    index(id_procedure_status) -- base idx end    
);

create
or replace view vw_user_proc05 as
SELECT
  p05.*, 
  u1.fullname usuario_nombre, u1.userlogin usuario_email,
--  u2.fullname operador_nombre, u2.userlogin operador_email,
  procs.description tramite,
  ps.status_desc tramite_status 
from
  proc05 p05
  join users u1 on u1.id = p05.id_user
  join procedures procs on procs.id = p05.id_procedure
  join procedure_status ps on ps.id = p05.id_procedure_status
  left outer join users u2 on u2.id = p05.id_user_operator
    ;



create table appointments (
    id int not null auto_increment primary key,
    dt datetime not null,
    id_up int not null,  -- up=user procedure (pk id from each table)
    id_procedure int not null,  -- procedure type id
    comment varchar(512)
);


create or replace view vw_appointments as
select up.*, ap.id id_appointment, ap.dt, ap.comments
from vw_user_procs up 
join appointments ap on ap.id_up = up.id and ap.id_procedure = up.id_procedure 
where up.id_procedure_status = 5;



create
or replace view vw_user_proc06 as
SELECT
  p06.*, 
  u1.fullname usuario_nombre, u1.userlogin usuario_email,
  u2.fullname operador_nombre, u2.userlogin operador_email,
  procs.description tramite,
  ps.status_desc tramite_status 
from
  proc06 p06
  join users u1 on u1.id = p06.id_user
  join procedures procs on procs.id = p06.id_procedure
  join procedure_status ps on ps.id = p06.id_procedure_status
  left outer join users u2 on u2.id = p06.id_user_operator
    ;


 -- keep this always at the end
create or replace view vw_user_procs as
select id, id_user, id_user_operator, id_procedure, id_procedure_status, tramite, tramite_status, usuario_nombre, usuario_email, operador_nombre, operador_email, sentri, sentri_vencimiento
from vw_user_proc01
union
select id, id_user, id_user_operator, id_procedure, id_procedure_status, tramite, tramite_status, usuario_nombre, usuario_email, operador_nombre, operador_email, sentri, sentri_vencimiento
from vw_user_proc02
union
select id, id_user, id_user_operator, id_procedure, id_procedure_status, tramite, tramite_status, usuario_nombre, usuario_email, operador_nombre, operador_email, sentri, sentri_vencimiento
from vw_user_proc03
union
select id, id_user, id_user_operator, id_procedure, id_procedure_status, tramite, tramite_status, usuario_nombre, usuario_email, operador_nombre, operador_email, sentri, sentri_vencimiento
from vw_user_proc04
union
select id, id_user, id_user_operator, id_procedure, id_procedure_status, tramite, tramite_status, usuario_nombre, usuario_email, operador_nombre, operador_email, sentri, sentri_vencimiento
from vw_user_proc05
union
select id, id_user, id_user_operator, id_procedure, id_procedure_status, tramite, tramite_status, usuario_nombre, usuario_email, operador_nombre, operador_email, sentri, sentri_vencimiento
from vw_user_proc06
;

select view_definition from information_schema.views where table_name='vw_user_proc01';



-- citas calendario
create table operators_by_date (
    id int not null auto_increment primary key,
    dt date not null UNIQUE,
    op_count int not null
);



DELIMITER //

DROP PROCEDURE IF EXISTS sp_citas_disponibles //

CREATE PROCEDURE sp_citas_disponibles (fecha date)
BEGIN  
  declare mc int; -- minutos x cita
  declare opf int; -- operadores x fecha
  declare cini, cfin, cinc time; -- cita inicio, cita fin from config
  declare min_disp int;  -- total de minutos para citas
  declare max_citas int;
  declare i int;

  select minutos_cita, citas_inicio, citas_fin into mc, cini, cfin from config limit 1;
  select op_count into opf from operators_by_date where dt = fecha limit 1;

  if (opf is null) then
    set opf = 3;
  end if;

  select time_to_sec(timediff(citas_fin, citas_inicio))/60 into min_disp from config limit 1;
  
  set max_citas = min_disp/mc;

  drop TEMPORARY table if exists citasbase;
  drop TEMPORARY table if exists citasdisponibles;

  CREATE TEMPORARY TABLE citasbase (fecha date, inicio time, fin time, citas int); 
  insert into citasbase 
  SELECT fecha,
    date_format(dt - interval minute(dt)%mc minute, '%H:%i') as inicio,
    date_format(dt + interval mc-minute(dt)%mc minute, '%H:%i') as fin,
    opf - COUNT(*) as citas
  FROM appointments where date(dt) = fecha
  GROUP BY inicio, fin
  ORDER BY inicio ASC;


  drop TEMPORARY table if exists citasdisponibles;
  create TEMPORARY table citasdisponibles (fecha date, inicio time, fin time, citas int);
  
  set i = max_citas;
  set cinc = cini;
  while i > 0 do
    insert into citasdisponibles (fecha, inicio, fin, citas) values (fecha, cinc, date_add(cinc, interval mc minute), opf);
    set cinc = date_add(cinc, interval mc minute);
    set i = i - 1;
  end while;

  delete from citasdisponibles where inicio in (select inicio from citasbase);

  select a.* from citasdisponibles a
  union 
  select b.* from citasbase b
  order by 1,2,3;
  
  -- select fecha, inicio, fin, opf - citas as citas_disponibles from citasbase;
  drop TEMPORARY table citasbase;
  drop TEMPORARY table citasdisponibles;
END 
//

DELIMITER ;

call sp_citas_disponibles('2022-08-23');




-- reset ------------------------------------------------------
delete from proc01 where id_user in (20, 21, 22);
delete from proc02 where id_user in (20, 21, 22);
delete from proc03 where id_user in (20, 21, 22);
delete from proc04 where id_user in (20, 21, 22);
delete from proc05 where id_user in (20, 21, 22);
delete from proc06 where id_user in (20, 21, 22);


create table tags (
  id int not null auto_increment primary key,
  tag varchar(50) not null UNIQUE,
  balance decimal(10,2) not null default 0
);
insert into tags(tag) values('100001'), ('100002'),('100003'), ('100004'), ('100005'), ('100006'), ('100007');


create table vehicles (
  id int not null auto_increment primary key,
  marca varchar(50) not null,
  linea varchar(50) not null,
  placa varchar(10) not null,
  modelo varchar(50) not null,
  color varchar(50) not null,
  id_user int not null,
  foreign key (id_user) references users(id),
  id_tag int not null,
  foreign key (id_tag) references tags(id)
);
insert into vehicles(marca, linea, placa, modelo, color, id_user, id_tag) 
values 
('TOYOTA', 'CAMRY', 'abc123', '2020', 'ROJO', 5, 1),
('FORD', 'EXPLORER', 'NNA123', '2010', 'GRIS', 13, 2),
('NISSAN', 'ROGUE', 'RNS123', '2015', 'VERDE', 13, 3)
;



-- create table user_vehicle (
--   id int not null auto_increment primary key,
--   id_user int not null,
--   id_vehicle int not null,
--   date_added datetime not null default CURRENT_TIMESTAMP,
--   date_changed datetime not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
--   foreign key (id_user) references users(id),
--   foreign key (id_vehicle) references vehicles(id)
-- );


-- create table tag_vehiche (
--   id int not null auto_increment primary key,
--   id_tag int not null,
--   id_vehicle int not null,
--   date_added datetime not null default CURRENT_TIMESTAMP,
--   date_changed datetime not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
--   foreign key (id_tag) references tags(id),
--   foreign key (id_vehicle) references vehicles(id)
-- );




create or replace view vw_vehicles
as
select 
  v.id id_vehicle, v.marca, v.linea, v.placa, v.modelo, v.color, v.id_user, v.id_tag,
  t.tag, t.balance
from vehicles v
left outer  join tags t on v.id_tag = t.id
where v.is_active = 1
;

create or replace view vw_tags
as
select 
  t.id id_tag, t.tag, t.balance,
  v.id id_vehicle, v.marca, v.linea, v.placa, v.modelo, v.color, v.id_user
from  
  tags t 
  left outer join vehicles v on v.id_tag = t.id
;


alter table config
  add anual_zaragoza_mx decimal(10,2) not null default 0,
  add anual_lerdo_mx decimal(10,2) not null default 0,
  add anual_mixto_mx decimal(10,2) not null default 0,
  add anual_zaragoza_us decimal(10,2) not null default 0,
  add anual_lerdo_us decimal(10,2) not null default 0,
  add anual_mixto_us decimal(10,2) not null default 0,
  add saldo_zaragoza_mx decimal(10,2) not null default 0,
  add saldo_lerdo_mx decimal(10,2) not null default 0,
  add saldo_zaragoza_us decimal(10,2) not null default 0,
  add saldo_lerdo_us decimal(10,2) not null default 0
  ;

alter table config drop column saldo_zaragoza_mx;
alter table config drop column saldo_lerdo_mx;
alter table config drop column saldo_zaragoza_us;
alter table config drop column saldo_lerdo_us;

alter table config
  add saldo_zaragoza1_mx decimal(10,2) not null default 0,
  add saldo_zaragoza2_mx decimal(10,2) not null default 0,
  add saldo_zaragoza1_us decimal(10,2) not null default 0,
  add saldo_zaragoza2_us decimal(10,2) not null default 0
  ;
  


alter table config
  add mbPuenteLive1 varchar(512),  
  add mbPuenteLive2 varchar(512),
  add mbPuenteLive3 varchar(512),
  add mbPuenteLive4 varchar(512),
  add mbPuenteLive5 varchar(512),
  add mbPuenteLive6 varchar(512),
  add mbPuenteLive7 varchar(512);




CREATE or replace VIEW `vw_user_proc02`  
AS SELECT `p02`.`id` AS `id`, `p02`.`id_user` AS `id_user`, `p02`.`id_user_operator` AS `id_user_operator`, `p02`.`id_procedure` AS `id_procedure`, 
`p02`.`id_procedure_status` AS `id_procedure_status`, `p02`.`start_dt` AS `start_dt`, `p02`.`last_update_dt` AS `last_update_dt`, 
`p02`.`finish_dt` AS `finish_dt`, `p02`.`dom_calle` AS `dom_calle`, `p02`.`dom_numero_ext` AS `dom_numero_ext`, `p02`.`dom_colonia` AS `dom_colonia`, 
`p02`.`dom_ciudad` AS `dom_ciudad`, `p02`.`dom_estado` AS `dom_estado`, `p02`.`dom_cp` AS `dom_cp`, `p02`.`fac_razon_social` AS `fac_razon_social`, 
`p02`.`fac_rfc` AS `fac_rfc`, `p02`.`fac_dom_fiscal` AS `fac_dom_fiscal`, `p02`.`fac_email` AS `fac_email`, `p02`.`fac_telefono` AS `fac_telefono`, 
`p02`.`veh_marca` AS `veh_marca`, `p02`.`veh_modelo` AS `veh_modelo`, `p02`.`veh_color` AS `veh_color`, `p02`.`veh_anio` AS `veh_anio`, 
`p02`.`veh_placas` AS `veh_placas`, `p02`.`veh_origen` AS `veh_origen`, `p02`.`conv_saldo` AS `conv_saldo`, `p02`.`conv_anualidad` AS `conv_anualidad`, 
`u1`.`fullname` AS `usuario_nombre`, `u1`.`userlogin` AS `usuario_email`, `u2`.`fullname` AS `operador_nombre`, `u2`.`userlogin` AS `operador_email`, 
`procs`.`description` AS `tramite`, `ps`.`status_desc` AS `tramite_status`, `p02`.`sentri` AS `sentri`, `p02`.`sentri_vencimiento` AS `sentri_vencimiento` 
,p02.motivo
FROM ((((`proc02` `p02` join `users` `u1` on((`u1`.`id` = `p02`.`id_user`))) join `procedures` `procs` on((`procs`.`id` = `p02`.`id_procedure`))) join `procedure_status` `ps` on((`ps`.`id` = `p02`.`id_procedure_status`))) left join `users` `u2` on((`u2`.`id` = `p02`.`id_user_operator`)))  ;





-- vw_user_procs
-- +---------------------+-
-- | id                  | 
-- | id_user             | 
-- | id_user_operator    | 
-- | id_procedure        | 
-- | id_procedure_status | 
-- | tramite             | 
-- | tramite_status      | 
-- | usuario_nombre      | 
-- | usuario_email       | 
-- | operador_nombre     | 
-- | operador_email      | 
-- | sentri              | 
-- | sentri_vencimiento  | 
-- | start_dt            | 
-- | last_update_dt      | 
-- | finish_dt           | 
-- +---------------------+-

create or replace view vw_proc_search as
select upg.*, proc01.veh_marca, proc01.veh_modelo, proc01.veh_color, proc01.veh_anio, proc01.veh_placas
from vw_user_procs upg left outer join proc01 on proc01.id = upg.id 
where upg.id_procedure = 1
union
select upg.*, proc02.veh_marca, proc02.veh_modelo, proc02.veh_color, proc02.veh_anio, proc02.veh_placas
from vw_user_procs upg left outer join proc02 on proc02.id = upg.id 
where upg.id_procedure = 2
union
select upg.*, proc03.veh1_marca, proc03.veh1_modelo, proc03.veh1_color, proc03.veh1_anio, proc03.veh1_placas
from vw_user_procs upg left outer join proc03 on proc03.id = upg.id 
where upg.id_procedure = 3
union
select upg.*, proc04.veh1_marca, proc04.veh1_modelo, proc04.veh1_color, proc04.veh1_anio, proc04.veh1_placas
from vw_user_procs upg left outer join proc04 on proc04.id = upg.id 
where upg.id_procedure = 4
union
select upg.*, proc05.veh_marca, proc05.veh_modelo, proc05.veh_color, proc05.veh_anio, proc05.veh_placas
from vw_user_procs upg left outer join proc05 on proc05.id = upg.id 
where upg.id_procedure = 5
union
select upg.*, proc06.veh_marca, proc06.veh_modelo, proc06.veh_color, proc06.veh_anio, proc06.veh_placas
from vw_user_procs upg left outer join proc06 on proc06.id = upg.id 
where upg.id_procedure = 6
;

ALTER TABLE proc01 ADD FULLTEXT(`veh_marca`, `veh_modelo`, `veh_color`, `veh_placas`);

alter table users add FULLTEXT(`fullname`);

select
  *
from
  vw_proc_search
where
    tramite  
    tramite_status,
    usuario_nombre,
    sentri,
    veh_marca,
    veh_modelo,
    veh_color,
    veh_anio,
    veh_placas
;


create table banorte3d (
  id int(11) NOT NULL AUTO_INCREMENT,
  status varchar(10) not null,
  eci varchar(256),
  cavv varchar(256),
  xdi varchar(256),
  ref3d varchar(256),

  PRIMARY KEY (id)
);

create table bntc (
  id int(11) NOT NULL AUTO_INCREMENT,
  dt datetime not null default current_timestamp,
  monto numeric(12,1) not null,
  tcn varchar(20) not null,
  fe varchar(8) not null,
  cs varchar(8) not null,
  tag varchar(64) not null,

  PRIMARY KEY (id)
);

create view vw_payments as
select 
  v.user_name as `Usuario`, v.marca as `Marca`, v.linea as `Linea`, v.modelo as `Modelo`, v.tag as `Tag`, 
  p.payment_dt as `Fecha-Hora`, p.amount as `Monto`, p.card_type as `Tipo de tarjeta`, p.bridge as `Puente`
from 
  payments p
  join vw_vehicles v on v.id = p.id_vehicle
;

alter table payments add column vmarca varchar(50) default '';
alter table payments add column vlinea varchar(50) default '';
alter table payments add column vplaca varchar(10)  default '';
alter table payments add column vmodelo varchar(50)  default '';
alter table payments add column vcolor varchar(50)  default '';
alter table payments add column vtag varchar(32)  default '';
alter table payments add column vtipo int(11) ;
alter table payments add column id_user int(11) ;
alter table payments add column username varchar(256)  default '';
alter table payments add column sentri varchar(64)  default '';

alter table payments drop column vmarca;
alter table payments drop column vlinea ;
alter table payments drop column vplaca ;
alter table payments drop column vmodelo ;
alter table payments drop column vcolor ;
alter table payments drop column vtag ;
alter table payments drop column vtipo ;
alter table payments drop column id_user ;
alter table payments drop column username ;
alter table payments drop column sentri ;



update payments p 
join vw_vehicles v on v.id = p.id_vehicle and v.tipo <> 2
set 
p.username = v.user_name,
p.vmarca = v.marca,
p.vlinea = v.linea,
p.vplaca = v.placa,
p.vmodelo = v.modelo,
p.vcolor = v.color,
p.vtag = v.tag,
p.vtipo = v.tipo,
p.id_user = v.id_user;

update payments p 
join users u on u.id = p.id_user  and u.sentri_number is not null
set 
p.sentri = u.sentri_number;