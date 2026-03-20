---
description: "Corre laravel-simplifier a un commit. Sin argumento: ultimo commit. Con hash: ese commit."
arguments:
  - name: commit
    description: "Hash del commit a analizar (opcional, default: ultimo commit)"
    required: false
---

Analiza los archivos modificados en el commit indicado y ejecuta el agente `laravel-simplifier` sobre ellos.

**Commit objetivo:** Si se proporciona `$ARGUMENTS`, usarlo como hash del commit. Si no, usar el ultimo commit (HEAD).

## Pasos

1. Determina el commit objetivo:
   - Si `$ARGUMENTS` tiene valor, usa ese hash: `git diff --name-only <hash>~1 <hash>`.
   - Si no, usa el ultimo commit: `git diff --name-only HEAD~1 HEAD`.
2. Lee cada archivo modificado para entender el contexto completo.
3. Lanza el agente `laravel-simplifier` (subagent_type: `laravel-simplifier:laravel-simplifier`) con las siguientes instrucciones:
   - Revisa los archivos modificados en el commit objetivo.
   - Simplifica el codigo para mayor claridad, consistencia y mantenibilidad.
   - Corrige cualquier bug o problema que encuentres.
   - Implementa las mejoras recomendadas.
   - Preserva toda la funcionalidad existente.
   - Sigue las convenciones del proyecto definidas en CLAUDE.md.
4. Despues de aplicar los cambios, ejecuta `vendor/bin/pint --dirty --format agent` para formatear el codigo.
5. Ejecuta los tests relacionados con los archivos modificados para verificar que nada se rompio.
6. Presenta un resumen conciso de los cambios realizados.
