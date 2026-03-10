import React from 'react';
import { motion } from 'framer-motion';

export const LoadingOverlay: React.FC = () => {
  return (
    <div
      style={{
        position: 'fixed',
        inset: 0,
        background: 'rgba(232, 245, 233, 0.85)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        zIndex: 9999
      }}
    >
      <motion.div
        initial={{ opacity: 0, scale: 0.8 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ duration: 0.25 }}
        style={{
          padding: '18px 26px',
          borderRadius: '14px',
          background: '#ffffff',
          boxShadow: '0 10px 32px rgba(0,0,0,0.18)',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          gap: 10
        }}
      >
        <motion.div
          animate={{ rotate: 360 }}
          transition={{ repeat: Infinity, ease: 'linear', duration: 1 }}
          style={{
            width: 36,
            height: 36,
            borderRadius: '50%',
            border: '4px solid #c8e6c9',
            borderTopColor: '#2e7d32'
          }}
        />
        <div style={{ fontSize: 14, fontWeight: 500, color: '#1b5e20' }}>Loading...</div>
      </motion.div>
    </div>
  );
};

