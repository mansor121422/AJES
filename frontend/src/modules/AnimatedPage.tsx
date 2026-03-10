import React from 'react';
import { motion } from 'framer-motion';

export const AnimatedPage: React.FC = () => {
  return (
    <motion.div
      initial={{ opacity: 0, y: 12 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.4, ease: 'easeOut' }}
      style={{ width: '100%', height: '100%' }}
    >
      {/* This component is meant to wrap existing CI4 content via a container div.
          In CI4 views, you can place an empty <div data-react-component="page"></div>
          above or around the main content and let CSS position it as needed. */}
    </motion.div>
  );
};

