import React from 'react';
import { motion } from 'framer-motion';

const cardVariants = {
  hidden: { opacity: 0, y: 24 },
  visible: (i: number) => ({
    opacity: 1,
    y: 0,
    transition: { delay: 0.08 * i, duration: 0.35, ease: 'easeOut' }
  })
};

export const AnimatedCardGrid: React.FC = () => {
  const items = [1, 2, 3];

  return (
    <div
      style={{
        display: 'grid',
        gap: '16px',
        gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))'
      }}
    >
      {items.map((item, idx) => (
        <motion.div
          key={item}
          custom={idx}
          initial="hidden"
          animate="visible"
          variants={cardVariants}
          whileHover={{ y: -4, boxShadow: '0 10px 28px rgba(0,0,0,0.12)' }}
          style={{
            padding: '18px 20px',
            borderRadius: '12px',
            background: '#ffffff',
            boxShadow: '0 6px 18px rgba(0,0,0,0.06)',
            border: '1px solid #e0f2f1'
          }}
        >
          <div style={{ fontWeight: 600, color: '#1b5e20', marginBottom: 6 }}>
            Animated card {item}
          </div>
          <div style={{ fontSize: 13, color: '#555' }}>
            This is a sample card animated with Framer Motion.
          </div>
        </motion.div>
      ))}
    </div>
  );
};

