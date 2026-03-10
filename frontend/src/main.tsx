import React from 'react';
import ReactDOM from 'react-dom/client';
import { AnimatePresence } from 'framer-motion';
import { AnimatedPage } from './modules/AnimatedPage';
import { AnimatedCardGrid } from './modules/AnimatedCardGrid';
import { AnimatedButton } from './modules/AnimatedButton';
import { LoadingOverlay } from './modules/LoadingOverlay';

type ComponentName = 'page' | 'cards' | 'button' | 'loading';

function mountComponent(el: HTMLElement, name: ComponentName) {
  const root = ReactDOM.createRoot(el);

  if (name === 'page') {
    root.render(
      <React.StrictMode>
        <AnimatePresence mode="wait">
          <AnimatedPage />
        </AnimatePresence>
      </React.StrictMode>
    );
  } else if (name === 'cards') {
    root.render(
      <React.StrictMode>
        <AnimatedCardGrid />
      </React.StrictMode>
    );
  } else if (name === 'button') {
    root.render(
      <React.StrictMode>
        <AnimatedButton />
      </React.StrictMode>
    );
  } else if (name === 'loading') {
    root.render(
      <React.StrictMode>
        <LoadingOverlay />
      </React.StrictMode>
    );
  }
}

// Auto-mount based on data attributes inside CI4 views.
document.querySelectorAll<HTMLElement>('[data-react-component]').forEach((el) => {
  const name = el.dataset.reactComponent as ComponentName | undefined;
  if (!name) return;
  mountComponent(el, name);
});

