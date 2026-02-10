import { useState } from 'react';

export default function useForceUpdate(): () => void {
    const [, setCount] = useState<number>(0);
    return () => { setCount((prevCount) => prevCount + 1); };
}
