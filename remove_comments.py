#!/usr/bin/env python3
import re
import os
import glob

def remove_php_comments(content):
    """Remove comentários de arquivos PHP preservando strings"""
    result = []
    in_string = False
    string_delimiter = None
    escaped = False
    in_block_comment = False
    in_line_comment = False
    i = 0
    
    while i < len(content):
        char = content[i]
        next_char = content[i+1] if i+1 < len(content) else ''
        
        if escaped:
            result.append(char)
            escaped = False
            i += 1
            continue
            
        if char == '\\' and in_string:
            escaped = True
            result.append(char)
            i += 1
            continue
        
        if in_block_comment:
            if char == '*' and next_char == '/':
                in_block_comment = False
                i += 2
                continue
            i += 1
            continue
        
        if in_line_comment:
            if char == '\n':
                in_line_comment = False
                result.append(char)
            i += 1
            continue
        
        if not in_string:
            if char == '/' and next_char == '*':
                in_block_comment = True
                i += 2
                continue
            if char == '/' and next_char == '/':
                in_line_comment = True
                i += 2
                continue
            if char == '#':
                in_line_comment = True
                i += 1
                continue
        
        if char in ['"', "'"] and not in_string:
            in_string = True
            string_delimiter = char
            result.append(char)
        elif char == string_delimiter and in_string:
            in_string = False
            string_delimiter = None
            result.append(char)
        else:
            result.append(char)
        
        i += 1
    
    text = ''.join(result)
    text = re.sub(r'\n\s*\n\s*\n+', '\n\n', text)
    
    return text

def process_file(filepath):
    """Processa um arquivo PHP removendo comentários"""
    print(f"Processando: {filepath}")
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        new_content = remove_php_comments(content)
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        
        print(f"✓ {filepath}")
        return True
    except Exception as e:
        print(f"✗ Erro em {filepath}: {e}")
        return False

def main():
    base_path = os.path.dirname(os.path.abspath(__file__))
    
    patterns = [
        'app/**/*.php',
        'routes/*.php',
        'config/*.php',
        '*.php'
    ]
    
    files_to_process = set()
    for pattern in patterns:
        full_pattern = os.path.join(base_path, pattern)
        files_to_process.update(glob.glob(full_pattern, recursive=True))
    
    total = len(files_to_process)
    success = 0
    
    print(f"Encontrados {total} arquivos PHP")
    print("-" * 50)
    
    for filepath in sorted(files_to_process):
        if process_file(filepath):
            success += 1
    
    print("-" * 50)
    print(f"Concluído: {success}/{total} arquivos processados com sucesso")

if __name__ == '__main__':
    main()
