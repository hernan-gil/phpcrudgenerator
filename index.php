<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tableDefinition = $_POST['table_definition'];

    // Aquí podrías agregar lógica para analizar la definición de la tabla y generar el CRUD
    // Por simplicidad, este ejemplo solo muestra la definición ingresada
    $crudCode = generateCrudCode($tableDefinition);
}

function generateCrudCode($tableDefinition) {
    // Extraer el nombre de la tabla
    preg_match('/CREATE TABLE `(\w+)`/', $tableDefinition, $matches);
    $tableName = $matches[1];

    // Extraer las columnas
    preg_match_all('/`(\w+)` (\w+)/', $tableDefinition, $columns);
    $columnNames = $columns[1];
    $columnTypes = $columns[2];

    // Generar el código CRUD
    $crudCode = "<?php\n";
    $crudCode .= "// Conexión a la base de datos\n";
    $crudCode .= "\$conn = new mysqli(\"localhost\", \"tu_usuario\", \"tu_contraseña\", \"tu_base_de_datos\");\n";
    $crudCode .= "if (\$conn->connect_error) {\n";
    $crudCode .= "    die(\"Conexión fallida: \" . \$conn->connect_error);\n";
    $crudCode .= "}\n\n";

    // Crear/Actualizar
    $crudCode .= "// Crear o actualizar registro\n";
    $crudCode .= "if (\$_SERVER[\"REQUEST_METHOD\"] == \"POST\") {\n";
    foreach ($columnNames as $column) {
        $crudCode .= "    \$$column = \$_POST['$column'];\n";
    }
    $crudCode .= "    if (isset(\$_POST['id']) && \$_POST['id'] != \"\") {\n";
    $crudCode .= "        // Modo edición\n";
    $crudCode .= "        \$id = \$_POST['id'];\n";
    $crudCode .= "        \$sql = \"UPDATE $tableName SET ";
    foreach ($columnNames as $column) {
        if ($column !== 'id') {
            $crudCode .= "$column='\".\$$column.\"', ";
        }
    }
    $crudCode = rtrim($crudCode, ', ') . " WHERE id=\$id\";\n";
    $crudCode .= "    } else {\n";
    $crudCode .= "        // Modo creación\n";
    $crudCode .= "        \$sql = \"INSERT INTO $tableName (";
    $crudCode .= implode(', ', $columnNames) . ") VALUES ('\" . ";
    $crudCode .= implode(" . \"', '\" . ", array_map(fn($col) => "\$$col", $columnNames)) . " . \"')\";\n";
    $crudCode .= "    }\n";
    $crudCode .= "    if (\$conn->query(\$sql) === TRUE) {\n";
    $crudCode .= "        header(\"Location: crud.php\");\n";
    $crudCode .= "    } else {\n";
    $crudCode .= "        echo \"Error: \" . \$sql . \"<br>\" . \$conn->error;\n";
    $crudCode .= "    }\n";
    $crudCode .= "}\n\n";

    // Eliminar
    $crudCode .= "// Eliminar registro\n";
    $crudCode .= "if (isset(\$_GET['delete'])) {\n";
    $crudCode .= "    \$id = \$_GET['delete'];\n";
    $crudCode .= "    \$sql = \"DELETE FROM $tableName WHERE id=\$id\";\n";
    $crudCode .= "    if (\$conn->query(\$sql) === TRUE) {\n";
    $crudCode .= "        header(\"Location: crud.php\");\n";
    $crudCode .= "    } else {\n";
    $crudCode .= "        echo \"Error: \" . \$sql . \"<br>\" . \$conn->error;\n";
    $crudCode .= "    }\n";
    $crudCode .= "}\n\n";
	
	// Editar
	$crudCode .= "// Obtener registro para editar\n";
	$crudCode .= "if (isset(\$_GET['edit'])) {\n";
	$crudCode .= "    \$id = \$_GET['edit'];\n";
	$crudCode .= "    \$sql = \"SELECT * FROM $tableName WHERE id=\$id\";\n";
	$crudCode .= "    \$result = \$conn->query(\$sql);\n";
	$crudCode .= "    if (\$result->num_rows > 0) {\n";
	$crudCode .= "    	\$row = \$result->fetch_assoc();\n";
    foreach ($columnNames as $column) {
        $crudCode .= "    \$$column = \$row['$column'];\n";
    }
	$crudCode .= "   	\$editMode = true;\n";
	$crudCode .= "    }\n";
	$crudCode .= "}\n\n";

    // Obtener todos los registros
    $crudCode .= "// Obtener todos los registros\n";
    $crudCode .= "\$sql = \"SELECT * FROM $tableName\";\n";
    $crudCode .= "\$result = \$conn->query(\$sql);\n";
    $crudCode .= "?>\n\n";

    // Generar HTML para mostrar los registros
    $crudCode .= "<!DOCTYPE html>\n<html lang=\"es\">\n<head>\n";
    $crudCode .= "    <meta charset=\"UTF-8\">\n";
    $crudCode .= "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
    $crudCode .= "    <title>CRUD $tableName</title>\n";
    $crudCode .= "    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css\" rel=\"stylesheet\">\n";
    $crudCode .= "</head>\n<body>\n";
    $crudCode .= "    <div class=\"container mt-5\">\n";
    $crudCode .= "        <h2 class=\"mb-4\">CRUD $tableName</h2>\n";
    $crudCode .= "        <form method=\"post\" action=\"\">\n";
    $crudCode .= "            <input type=\"hidden\" name=\"id\" value=\"<?php echo isset(\$editMode) && \$editMode ? \$id : ''; ?>\">\n";
    foreach ($columnNames as $column) {
        if ($column !== 'id') {
            $crudCode .= "            <div class=\"mb-3\">\n";
            $crudCode .= "                <label for=\"$column\" class=\"form-label\">" . ucfirst($column) . "</label>\n";
            $crudCode .= "                <input type=\"text\" class=\"form-control\" id=\"$column\" name=\"$column\" value=\"<?php echo isset(\$$column) ? \$$column : ''; ?>\" required>\n";
            $crudCode .= "            </div>\n";
        }
    }
    $crudCode .= "            <button type=\"submit\" class=\"btn btn-primary\"><?php echo isset(\$editMode) && \$editMode ? 'Actualizar' : 'Guardar'; ?></button>\n";
    $crudCode .= "            <?php if (isset(\$editMode) && \$editMode): ?>\n";
    $crudCode .= "                <a href=\"crud.php\" class=\"btn btn-secondary\">Cancelar</a>\n";
    $crudCode .= "            <?php endif; ?>\n";
    $crudCode .= "        </form>\n\n";

    $crudCode .= "        <table class=\"table table-striped mt-4\">\n";
    $crudCode .= "            <thead>\n";
    $crudCode .= "                <tr>\n";
    foreach ($columnNames as $column) {
        $crudCode .= "                    <th>" . ucfirst($column) . "</th>\n";
    }
    $crudCode .= "                    <th>Acciones</th>\n";
    $crudCode .= "                </tr>\n";
    $crudCode .= "            </thead>\n";
    $crudCode .= "            <tbody>\n";
    $crudCode .= "                <?php\n";
    $crudCode .= "                if (\$result->num_rows > 0) {\n";
    $crudCode .= "                    while(\$row = \$result->fetch_assoc()) {\n";
    $crudCode .= "                        echo \"<tr>\";\n";
    foreach ($columnNames as $column) {
        $crudCode .= "                        echo \"<td>{\$row['$column']}</td>\";\n";
    }
    $crudCode .= "                        echo \"<td>\n";
    $crudCode .= "                                <a href='crud.php?edit={\$row['id']}' class='btn btn-warning btn-sm'>Editar</a>\n";
    $crudCode .= "                                <a href='crud.php?delete={\$row['id']}' class='btn btn-danger btn-sm'>Eliminar</a>\n";
    $crudCode .= "                              </td>\";\n";
    $crudCode .= "                        echo \"</tr>\";\n";
    $crudCode .= "                    }\n";
    $crudCode .= "                } else {\n";
    $crudCode .= "                    echo \"<tr><td colspan='" . (count($columnNames) + 1) . "'>No hay registros</td></tr>\";\n";
    $crudCode .= "                }\n";
    $crudCode .= "                ?>\n";
    $crudCode .= "            </tbody>\n";
    $crudCode .= "        </table>\n";
    $crudCode .= "    </div>\n";
    $crudCode .= "    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js\"></script>\n";
    $crudCode .= "</body>\n</html>\n";

    $crudCode .= "<?php\n";
    $crudCode .= "\$conn->close();\n";
    $crudCode .= "?>\n";

    return $crudCode;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Generador de CRUD</h2>
        <form method="post" action="">
            <div class="mb-3">
                <label for="table_definition" class="form-label">Definición de la Tabla (MySQL)</label>
                <textarea class="form-control" id="table_definition" name="table_definition" rows="10" required><?php echo isset($tableDefinition) ? htmlspecialchars($tableDefinition) : ''; ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Generar CRUD</button>
        </form>

        <?php if (isset($crudCode)): ?>
            <h3 class="mt-5">Código Generado</h3>
            <pre class="bg-light p-3"><?php echo htmlspecialchars($crudCode); ?></pre>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>