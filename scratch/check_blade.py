
import re

def check_blade_balance(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    stack = []
    pairs = {
        '@if': '@endif',
        '@foreach': '@endforeach',
        '@auth': '@endauth',
        '@section': '@endsection',
        '@push': '@endpush',
        '@unless': '@endunless',
        '@for': '@endfor',
        '@while': '@endwhile',
        '@can': '@endcan',
        '@cannot': '@endcannot',
        '@guest': '@endguest'
    }
    
    # Directives that can be self-closing or multi-line
    # We only care about multi-line ones for this error
    
    shorthand_sections = 0

    for i, line in enumerate(lines):
        line_num = i + 1
        
        # Match directives (avoid matching inside strings if possible, but regex is simple here)
        # We need to handle inline @if ... @endif too
        
        # Find all start directives on this line
        starts = re.findall(r'@(if|foreach|auth|section|unless|for|while|can|cannot|guest)\b', line)
        # Find all end directives on this line
        ends = re.findall(r'@(endif|endforeach|endauth|endsection|endpush|endunless|endfor|endwhile|endcan|endcannot|endguest)\b', line)
        
        # Special case: @push and @endpush
        if '@push' in line:
            stack.append(('@push', line_num))
        
        for s in starts:
            # Check for shorthand section @section('title', 'val')
            if s == 'section' and ',' in line and ')' in line:
                shorthand_sections += 1
                continue
            stack.append(('@' + s, line_num))
            
        for e in ends:
            if not stack:
                print(f"Error: Unexpected @{e} at line {line_num}")
                continue
            
            top_directive, top_line = stack.pop()
            expected_end = pairs.get(top_directive)
            
            if '@' + e != expected_end:
                print(f"Error: Mismatched @{e} at line {line_num}. Expected {expected_end} for {top_directive} from line {top_line}")
                # Put it back to try to keep going? No, just stop or push back.
                stack.append((top_directive, top_line))

    if stack:
        for d, l in stack:
            print(f"Error: Unclosed {d} from line {l}")
    else:
        print("All clear!")

if __name__ == "__main__":
    check_blade_balance("c:/Users/HP/Desktop/quilify/resources/views/service-landing.blade.php")
