import React from 'react';
import { motion } from 'framer-motion';

export const AnimatedButton: React.FC = () => {
  return (
    <motion.button
      whileHover={{ scale: 1.04, y: -1, boxShadow: '0 6px 18px rgba(0,0,0,0.18)' }}
      whileTap={{ scale: 0.97, y: 1 }}
      transition={{ type: 'spring', stiffness: 260, damping: 18 }}
      style={{
        padding: '10px 20px',
        borderRadius: '999px',
        border: 'none',
        fontWeight: 600,
        fontSize: 14,
        cursor: 'pointer',
        background: 'linear-gradient(135deg, #81c784 0%, #2e7d32 100%)',
        color: '#ffffff',
        display: 'inline-flex',
        alignItems: 'center',
        gap: 8
      }}
    >
      <span>Animated action</span>
    </motion.button>
  );
};

