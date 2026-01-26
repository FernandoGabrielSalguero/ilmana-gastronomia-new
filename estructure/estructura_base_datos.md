📚 Estructura completa de la base de datos: u437094107_viandas_sch00l
📄 Tabla: Colegios
Columna	Tipo	Nulo	Clave	Default	Extra
Id	int(11)	NO	PRI		auto_increment
Nombre	varchar(100)	YES			
Dirección	varchar(255)	YES			

📄 Tabla: Correos_Log
Columna	Tipo	Nulo	Clave	Default	Extra
Id	bigint(20) unsigned	NO	PRI		auto_increment
Usuario_Id	bigint(20) unsigned	YES	MUL		
Correo	varchar(255)	NO	MUL		
Nombre	varchar(255)	YES			
Asunto	varchar(255)	NO			
Template	varchar(120)	YES	MUL		
Mensaje_HTML	longtext	YES			
Mensaje_Text	longtext	YES			
Estado	enum('enviado','fallido')	NO	MUL	enviado	
Error	text	YES			
Meta	longtext	YES			
Creado_En	datetime	NO	MUL	current_timestamp()	

📄 Tabla: Cursos
Columna	Tipo	Nulo	Clave	Default	Extra
Id	int(11)	NO	PRI		auto_increment
Nombre	varchar(100)	YES			
Colegio_Id	int(11)	YES	MUL		
Nivel_Educativo	enum('Inicial','Primaria','Secundaria','Sin Curso Asignado')	NO		Sin Curso Asignado	

🔗 Relaciones:
Columna Colegio_Id referencia a Colegios.Id
📄 Tabla: Detalle_Pedidos_Cuyo_Placa
Columna	Tipo	Nulo	Clave	Default	Extra
id	int(11)	NO	PRI		auto_increment
pedido_id	int(11)	NO	MUL		
planta	varchar(255)	NO			
turno	varchar(50)	NO			
menu	varchar(255)	NO			
cantidad	int(11)	NO			

🔗 Relaciones:
Columna pedido_id referencia a Pedidos_Cuyo_Placa.id
📄 Tabla: Hijos
Columna	Tipo	Nulo	Clave	Default	Extra
Id	int(11)	NO	PRI		auto_increment
Nombre	varchar(100)	YES			
Preferencias_Alimenticias	text	YES			
Colegio_Id	int(11)	YES	MUL		
Curso_Id	int(11)	YES	MUL		

🔗 Relaciones:
Columna Colegio_Id referencia a Colegios.Id
Columna Curso_Id referencia a Cursos.Id
📄 Tabla: Menú
Columna	Tipo	Nulo	Clave	Default	Extra
Id	int(11)	NO	PRI		auto_increment
Nombre	varchar(100)	YES			
Fecha_entrega	date	YES			
Fecha_hora_compra	datetime	YES			
Fecha_hora_cancelacion	datetime	YES			
Precio	decimal(10,2)	YES			
Estado	enum('En venta','Sin stock')	YES			
Nivel_Educativo	enum('Inicial','Primaria','Secundaria','Sin Curso Asignado')	NO		Sin Curso Asignado	

📄 Tabla: Pedidos_Comida
Columna	Tipo	Nulo	Clave	Default	Extra
Id	int(11)	NO	PRI		auto_increment
Fecha_entrega	date	YES			
Preferencias_alimenticias	text	YES			
Hijo_Id	int(11)	YES	MUL		
Fecha_pedido	datetime	NO			
Estado	enum('Procesando','Cancelado','Entregado')	NO			
Menú_Id	int(11)	NO	MUL		
motivo_cancelacion	varchar(255)	YES			

🔗 Relaciones:
Columna Hijo_Id referencia a Hijos.Id
Columna Menú_Id referencia a Menú.Id
📄 Tabla: Pedidos_Cuyo_Placa
Columna	Tipo	Nulo	Clave	Default	Extra
id	int(11)	NO	PRI		auto_increment
usuario_id	int(11)	NO	MUL		
fecha	date	NO			
created_at	timestamp	YES		current_timestamp()	

🔗 Relaciones:
Columna usuario_id referencia a Usuarios.Id
📄 Tabla: Pedidos_Saldo
Columna	Tipo	Nulo	Clave	Default	Extra
Id	int(11)	NO	PRI		auto_increment
Usuario_Id	int(11)	YES	MUL		
Saldo	decimal(10,2)	YES			
Estado	enum('Pendiente de aprobacion','Cancelado','Aprobado')	YES			
Comprobante	varchar(255)	YES			
Fecha_pedido	datetime	YES			
Observaciones	text	YES			

🔗 Relaciones:
Columna Usuario_Id referencia a Usuarios.Id
📄 Tabla: Preferencias_Alimenticias
Columna	Tipo	Nulo	Clave	Default	Extra
Id	int(11)	NO	PRI		auto_increment
Nombre	varchar(100)	YES			

📄 Tabla: Representantes_Colegios
Columna	Tipo	Nulo	Clave	Default	Extra
Id	int(11)	NO	PRI		auto_increment
Representante_Id	int(11)	NO	MUL		
Colegio_Id	int(11)	NO	MUL		

🔗 Relaciones:
Columna Representante_Id referencia a Usuarios.Id
Columna Colegio_Id referencia a Colegios.Id
📄 Tabla: Usuarios
Columna	Tipo	Nulo	Clave	Default	Extra
Id	int(11)	NO	PRI		auto_increment
Nombre	varchar(100)	YES			
Usuario	varchar(100)	YES			
Contrasena	varchar(255)	YES			
Telefono	varchar(15)	YES			
Correo	varchar(100)	YES			
Pedidos_saldo	text	YES			
Saldo	decimal(10,2)	YES		0.00	
Pedidos_comida	text	YES			
Rol	enum('papas','hyt_agencia','hyt_admin','cocina','representante','administrador','cuyo_placa','transporte_ld')	NO			
Hijos	text	YES			
Estado	enum('activo','inactivo')	NO		activo	

📄 Tabla: Usuarios_Hijos
Columna	Tipo	Nulo	Clave	Default	Extra
Usuario_Id	int(11)	NO	PRI		
Hijo_Id	int(11)	NO	PRI		

🔗 Relaciones:
Columna Usuario_Id referencia a Usuarios.Id
Columna Hijo_Id referencia a Hijos.Id
📄 Tabla: Vista_Consolidada
Columna	Tipo	Nulo	Clave	Default	Extra
Usuario_Id	int(11)	NO		0	
Usuario_Nombre	varchar(100)	YES			
Usuario_Usuario	varchar(100)	YES			
Usuario_Contrasena	varchar(255)	YES			
Usuario_Telefono	varchar(15)	YES			
Usuario_Correo	varchar(100)	YES			
Usuario_Pedidos_saldo	text	YES			
Usuario_Saldo	decimal(10,2)	YES		0.00	
Usuario_Pedidos_comida	text	YES			
Usuario_Rol	enum('papas','hyt_agencia','hyt_admin','cocina','representante','administrador','cuyo_placa','transporte_ld')	NO			
Usuario_Hijos	text	YES			
Hijo_Id	int(11)	NO		0	
Hijo_Nombre	varchar(100)	YES			
Hijo_Colegio_Id	int(11)	YES			
Hijo_Curso_Id	int(11)	YES			
Hijo_Preferencias_Alimenticias	text	YES			
Colegio_Id	int(11)	NO		0	
Colegio_Nombre	varchar(100)	YES			
Curso_Id	int(11)	NO		0	
Curso_Nombre	varchar(100)	YES			
Preferencia_Id	int(11)	NO		0	
Preferencia_Nombre	varchar(100)	YES			

📄 Tabla: destinos_hyt
Columna	Tipo	Nulo	Clave	Default	Extra
id	int(11)	NO	PRI		auto_increment
nombre	varchar(255)	NO			

📄 Tabla: detalle_pedidos_hyt
Columna	Tipo	Nulo	Clave	Default	Extra
id	int(11)	NO	PRI		auto_increment
pedido_id	int(11)	YES	MUL		
nombre	varchar(255)	NO			
cantidad	int(11)	NO			
precio	decimal(10,2)	NO			
observaciones	text	YES			

🔗 Relaciones:
Columna pedido_id referencia a pedidos_hyt.id
📄 Tabla: hyt_admin_agencia
Columna	Tipo	Nulo	Clave	Default	Extra
id	int(11)	NO	PRI		auto_increment
hyt_admin_id	int(11)	NO	MUL		
hyt_agencia_id	int(11)	NO	MUL		

🔗 Relaciones:
Columna hyt_admin_id referencia a Usuarios.Id
Columna hyt_agencia_id referencia a Usuarios.Id
📄 Tabla: notificaciones_cocina
Columna	Tipo	Nulo	Clave	Default	Extra
id	int(11)	NO	PRI		auto_increment
tipo	enum('pedido','cancelacion','modificacion')	NO			
descripcion	text	YES			
estado	enum('pendiente','visto')	YES		pendiente	
usuario_id	int(11)	NO			
fecha_hora	timestamp	YES		current_timestamp()	

📄 Tabla: pedidos_hyt
Columna	Tipo	Nulo	Clave	Default	Extra
id	int(11)	NO	PRI		auto_increment
nombre_agencia	varchar(255)	NO			
correo_electronico_agencia	varchar(255)	YES			
fecha_pedido	date	NO			
fecha_modificacion	timestamp	YES		current_timestamp()	on update current_timestamp()
fecha_eliminacion	date	YES			
estado	enum('vigente','eliminado')	YES		vigente	
interno	int(11)	NO			
hora_salida	time	NO			
destino_id	int(11)	YES	MUL		
hyt_admin_id	int(11)	YES			
observaciones	text	YES		'Sin observaciones'	
estado_saldo	enum('Pagado','Adeudado')	YES		Adeudado	
fecha_salida	date	YES			

🔗 Relaciones:
Columna destino_id referencia a destinos_hyt.id
📄 Tabla: precios_hyt
Columna	Tipo	Nulo	Clave	Default	Extra
id	int(11)	NO	PRI		auto_increment
nombre	text	YES			
precio	decimal(10,2)	NO			
en_venta	tinyint(1)	YES		1	
