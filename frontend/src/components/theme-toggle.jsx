import {Moon, Sun} from 'lucide-react';

import {Button} from '@/components/ui/button';
import {useTheme} from '@/components/theme-provider';

export function ThemeToggle() {
    const {theme, setTheme} = useTheme();
    const isDark = theme === 'dark';

    return (
        <Button
            type="button"
            variant="outline"
            size="icon"
            className="h-9 w-9"
            onClick={() => setTheme(isDark ? 'light' : 'dark')}
        >
            {isDark ? <Sun className="size-4"/> : <Moon className="size-4"/>}
            <span className="sr-only">Toggle color theme</span>
        </Button>
    );
}
